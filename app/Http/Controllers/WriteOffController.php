<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Unit_measurement;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Illuminate\Support\Facades\Log;

class WriteOffController extends Controller
{
    // 1) LIST all "write off" documents for the user’s warehouse, or for all if you want
    public function index(Request $request)
    {
        // Suppose your DocumentType for write-off has code='writeOff'
        $docType = DocumentType::where('code', 'write_off')->first();
        Log::info($docType);
        if (!$docType) {
            return response()->json(['error' => 'No "writeOff" document type found'], 404);
        }

        // If user has a warehouse:
        $warehouse = Warehouse::where('manager_id', Auth::id())->first();
        if (!$warehouse) {
            return response()->json(['error' => 'No warehouse for this user'], 422);
        }

        // fetch docs
        $documents = Document::where('document_type_id', $docType->id)
            // ->where('to_warehouse_id', $warehouse->id) // or from_warehouse_id depending on logic
            ->with(['documentItems'])
            ->get();

        return response()->json($documents, 200);
    }


    // 2) SHOW single write-off doc (by ID)
    public function show($id)
    {
        $doc = Document::with(['documentItems'])->findOrFail($id);
        // Optionally ensure doc type is 'writeOff'
        if ($doc->documentType && $doc->documentType->code !== 'writeOff') {
            return response()->json(['error' => 'Document is not writeOff'], 400);
        }
        return response()->json($doc, 200);
    }


    public function store(Request $request)
{
    // For debugging the incoming payload (optional)
    Log::info($request->all());

    // Validate the incoming data
    $validated = $request->validate([
        'document_date' => 'required|date',
        'items'         => 'required|array|min:1',
        'doc_type'      => 'string',
        // 'comments' => 'nullable|string' if you want to handle comments
    ]);

    DB::beginTransaction();
    try {
        // 1) Find or fail the document type based on the given doc_type (e.g., "write_off")
        $docType = DocumentType::where('code', $validated['doc_type'])->firstOrFail();

        // 2) Find the user's warehouse (assumed to be managed by the current user)
        $warehouse = Warehouse::where('manager_id', Auth::id())->first();
        if (!$warehouse) {
            return response()->json(['error' => 'No warehouse for this user'], 422);
        }

        // 3) Create the main Document (header)
        $doc = new Document();
        $doc->document_type_id  = $docType->id;
        $doc->status            = '-'; // negative for "write off"
        $doc->from_warehouse_id = $warehouse->id; // writing off FROM this warehouse
        $doc->to_warehouse_id   = 0;             // or null, if not applicable
        $doc->document_date     = $validated['document_date'];
        $doc->comments          = $request->input('comments', '');
        $doc->save();

        // We'll collect "written off" lines to create DocumentItems
        $writtenOffItems = [];

        // 4) For each item, do the partial-ratio logic
        $items = $validated['items'];
        foreach ($items as $row) {
            // The mobile payload uses 'product_id', 'unit_id', 'quantity'
            $productId = $row['product_id'] ?? null;
            $unitId    = $row['unit_id'] ?? null;
            $qty       = floatval($row['quantity'] ?? 0);

            // Look up the actual UnitMeasurement row
            $unitModel = Unit_measurement::findOrFail($unitId);

            // Find the matching WarehouseItem (which references the "unit_measurement" column by name)
            $whItem = WarehouseItem::where('warehouse_id', $warehouse->id)
                ->where('product_subcard_id', $productId)
                ->where('unit_measurement', $unitModel->name)
                ->first();

            if (!$whItem) {
                throw new \Exception("Product #{$productId} / unit_id #{$unitId} not found in warehouse #{$warehouse->id}");
            }
            if ($whItem->quantity < $qty) {
                throw new \Exception("Not enough stock for product #{$productId} in warehouse #{$warehouse->id}");
            }

            // Old values before write-off
            $oldQty    = $whItem->quantity;
            $oldBrutto = $whItem->brutto;
            $oldNetto  = $whItem->netto;
            $oldPrice  = $whItem->price;
            $oldSum    = $whItem->total_sum;
            $oldCost   = $whItem->cost_price;
            $oldAddExp = $whItem->additional_expenses;

            // Partial ratio for the quantity to write off
            $ratio = $oldQty > 0 ? ($qty / $oldQty) : 0;

            // Calculate the amounts for the write-off portion
            $woBrutto = round($oldBrutto * $ratio, 2);
            $woNetto  = round($oldNetto * $ratio, 2);
            $woSum    = round($oldSum * $ratio, 2);
            $woAddExp = round($oldAddExp * $ratio, 2);

            // Subtract the write-off quantity from the warehouse item
            $whItem->quantity -= $qty;
            if ($whItem->quantity > 0) {
                $newRatio = $whItem->quantity / $oldQty;
                $whItem->brutto              = round($oldBrutto * $newRatio, 2);
                $whItem->netto               = round($oldNetto * $newRatio, 2);
                $whItem->total_sum           = round($oldSum * $newRatio, 2);
                $whItem->additional_expenses = round($oldAddExp * $newRatio, 2);
            } else {
                // Fully depleted: set to 0
                $whItem->brutto              = 0;
                $whItem->netto               = 0;
                $whItem->total_sum           = 0;
                $whItem->additional_expenses = 0;
            }
            $whItem->save();

            // Calculate net_unit_weight for the written-off portion: netto per unit of write-off quantity
            $netUnitWeight = ($qty > 0) ? round($woNetto / $qty, 4) : 0;

            // Collect data for this write-off line
            $writtenOffItems[] = [
                'product_subcard_id'  => $productId,
                'unit_measurement_id' => $unitModel->id, // storing the unit_measurement id as well
                'quantity'            => $qty,
                'brutto'              => $woBrutto,
                'netto'               => $woNetto,
                'price'               => $oldPrice,
                'total_sum'           => $woSum,
                'cost_price'          => $oldCost,
                'additional_expenses' => $woAddExp,
                'net_unit_weight'     => $netUnitWeight,
            ];
        }

        // 5) Create DocumentItems for each write-off line
        foreach ($writtenOffItems as $wi) {
            DocumentItem::create(array_merge($wi, [
                'document_id' => $doc->id,
            ]));
        }

        DB::commit();

        return response()->json([
            'success'     => true,
            'message'     => 'Write-off document created successfully',
            'document_id' => $doc->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}



    // 4) UPDATE existing write-off doc
    public function update(Request $request, Document $document)
{
    // 1) Validate the “header” data
    $data = $request->validate([
        'provider_id'           => 'nullable|integer',
        'document_date'         => 'nullable|date',
        'assigned_warehouse_id' => 'nullable|integer', // or to_warehouse_id
        // etc.
    ]);

    // 2) Update the Document "header"
    $document->update([
        'provider_id'     => $data['provider_id'] ?? null,
        'document_date'   => $data['document_date'] ?? now(),
        'to_warehouse_id' => $data['assigned_warehouse_id'] ?? null,
        // possibly keep document_type_id if it's an 'income' doc
    ]);

    // Identify the warehouse that receives goods for an "income" doc
    $warehouseId = $document->to_warehouse_id;

    // 3) Revert old items from warehouse (only if you want to fully re-apply them)
    $oldItems = $document->documentItems()->get();
    if ($warehouseId) {
        foreach ($oldItems as $oldItem) {
            $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                ->where('product_subcard_id', $oldItem->product_subcard_id)
                ->where('unit_measurement', $oldItem->unit_measurement)
                ->first();
            if ($whItem) {
                // Subtract the old quantity from the warehouse
                $whItem->quantity -= $oldItem->quantity;
                $whItem->brutto   -= $oldItem->brutto;
                $whItem->netto    -= $oldItem->netto;
                // If you track total_sum, cost_price, etc., update them here
                $whItem->save();
            }
        }
    }

    // 4) Update document items with net_unit_weight calculation
    $items = $request->input('products', []);
    $existingIds = [];
    foreach ($items as $itemData) {
        // Calculate net_unit_weight if quantity > 0
        if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
            $itemData['net_unit_weight'] = round($itemData['netto'] / $itemData['quantity'], 4);
        } else {
            $itemData['net_unit_weight'] = 0;
        }

        if (!empty($itemData['id'])) {
            // Update existing item
            $docItem = $document->documentItems()->find($itemData['id']);
            if ($docItem) {
                $docItem->update($itemData);
                $existingIds[] = $docItem->id;
            }
        } else {
            // Create new DocumentItem
            $newItem = $document->documentItems()->create($itemData);
            $existingIds[] = $newItem->id;
        }
    }

    // Delete items that were removed on the front-end
    $document->documentItems()
        ->whereNotIn('id', $existingIds)
        ->delete();

    // 5) Apply new items to the warehouse
    if ($warehouseId) {
        $updatedItems = $document->documentItems()->get();
        foreach ($updatedItems as $updItem) {
            $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                ->where('product_subcard_id', $updItem->product_subcard_id)
                ->where('unit_measurement', $updItem->unit_measurement)
                ->first();
            if (!$whItem) {
                // Create new warehouse row if necessary
                $whItem = new WarehouseItem();
                $whItem->warehouse_id = $warehouseId;
                $whItem->product_subcard_id = $updItem->product_subcard_id;
                $whItem->unit_measurement = $updItem->unit_measurement;
                $whItem->quantity = 0;
                $whItem->brutto = 0;
                $whItem->netto = 0;
                // Set price, total_sum, cost_price if needed
            }
            // Add the new item quantity to warehouse record
            $whItem->quantity += $updItem->quantity;
            $whItem->brutto   += $updItem->brutto;
            $whItem->netto    += $updItem->netto;
            // Recalculate other fields if needed
            $whItem->save();
        }
    }

    // 6) Update or create Expenses
    $expenseData = $request->input('expenses', []);
    $existingExpIds = [];
    foreach ($expenseData as $exp) {
        if (!empty($exp['id'])) {
            $expense = $document->expenses()->find($exp['id']);
            if ($expense) {
                $expense->update([
                    'name'   => $exp['name'] ?? '',
                    'amount' => $exp['amount'] ?? 0,
                ]);
                $existingExpIds[] = $expense->id;
            }
        } else {
            $newExp = $document->expenses()->create([
                'name'   => $exp['name'] ?? '',
                'amount' => $exp['amount'] ?? 0,
            ]);
            $existingExpIds[] = $newExp->id;
        }
    }
    // Delete any expenses not present in the request
    $document->expenses()
        ->whereNotIn('id', $existingExpIds)
        ->delete();

    // 7) Delete the Document if there are no items left
    $newCount = $document->documentItems()->count();
    if ($newCount === 0) {
        $document->delete();
        return response()->json([
            'success' => true,
            'deleted' => true,
            'message' => 'Document had no items, so it was deleted.'
        ]);
    }

    return response()->json([
        'success' => true,
        'deleted' => false,
        'message' => 'Document updated.',
        'document' => $document->fresh(['documentItems', 'expenses']),
    ]);
}

    // 5) DELETE (remove) the doc
    public function destroy($id)
    {
        $doc = Document::findOrFail($id);
        // ensure doc type is 'writeOff'
        if ($doc->documentType && $doc->documentType->code !== 'writeOff') {
            return response()->json(['error' => 'Document is not writeOff'], 400);
        }

        $doc->delete();
        return response()->json(['success'=>true, 'message'=>'Write-off deleted'], 200);
    }
}
