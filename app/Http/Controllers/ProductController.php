<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\WarehouseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    public function allHistories()
    {
        $documents = Document::with(['documentType', 'provider', 'documentItems'])->get();

        $results = [];
        foreach ($documents as $doc) {
            $typeCode = $doc->documentType ? $doc->documentType->code : '';
            $providerName = $doc->provider ? $doc->provider->name : '';
            $docTotal = $doc->documentItems->sum('total_sum'); // or any other sum logic

            $results[] = [
                'doc_id'          => $doc->id,
                'document_number' => $doc->document_number,     // if you have it
                'type'            => $typeCode,                 // e.g. 'income', 'sale'
                'document_date'   => $doc->document_date,
                'provider_name'   => $providerName,
                'doc_total_sum'   => $docTotal,
            ];
        }

        return response()->json($results);
    }


    public function index()
{
    // Возвращаем все документы с типом и items
    $documents = Document::with(['documentType', 'documentItems'])
        ->orderBy('id', 'desc')
        ->get();

    // При желании можно добавить computed_total
    $documents->each(function($doc){
        $doc->computed_total = $doc->documentItems->sum('total_sum');
    });

    return response()->json($documents);
}


public function show(Document $document)
{
    // Подгружаем items, expenses, provider и т.д.
    $document->load(['documentItems', 'expenses', 'provider', 'documentType']);
    // Можно тоже сделать sum, если нужно:
    $document->computed_total = $document->documentItems->sum('total_sum');

    return response()->json($document);
}

public function update(Request $request, Document $document)
{
    // 1) Validate the “header” data
    $data = $request->validate([
        'provider_id'          => 'nullable|integer',
        'document_date'        => 'nullable|date',
        'assigned_warehouse_id'=> 'nullable|integer', // or to_warehouse_id
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
                // etc. If you track total_sum, cost_price, or partial average, adapt as needed
                $whItem->save();
            }
        }
    }

    // 4) Update document items
    $items = $request->input('products', []);
    $existingIds = [];

    foreach ($items as $itemData) {
        if (! empty($itemData['id'])) {
            // Update existing
            $docItem = $document->documentItems()->find($itemData['id']);
            if ($docItem) {
                $docItem->update($itemData);
                $existingIds[] = $docItem->id;
            }
        } else {
            // Create new
            $newItem = $document->documentItems()->create($itemData);
            $existingIds[] = $newItem->id;
        }
    }

    // Delete items user removed in the front-end
    $document->documentItems()
        ->whereNotIn('id', $existingIds)
        ->delete();

    // 5) Apply new items to the warehouse
    //    - For each updated item, add it back into the warehouse
    if ($warehouseId) {
        $updatedItems = $document->documentItems()->get();
        foreach ($updatedItems as $updItem) {
            $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                ->where('product_subcard_id', $updItem->product_subcard_id)
                ->where('unit_measurement', $updItem->unit_measurement)
                ->first();

            if (! $whItem) {
                // create a new row in warehouse_items if needed
                $whItem = new WarehouseItem();
                $whItem->warehouse_id = $warehouseId;
                $whItem->product_subcard_id = $updItem->product_subcard_id;
                $whItem->unit_measurement = $updItem->unit_measurement;
                $whItem->quantity = 0;
                $whItem->brutto = 0;
                $whItem->netto = 0;
                // etc. for price, total_sum, cost_price if you have a logic
            }

            // Add the new item quantity
            $whItem->quantity += $updItem->quantity;
            $whItem->brutto   += $updItem->brutto;
            $whItem->netto    += $updItem->netto;
            // e.g. recalc total_sum or cost_price if needed
            $whItem->save();
        }
    }

    // 6) Update or create Expenses
    $expenseData = $request->input('expenses', []);
    $existingExpIds = [];
    foreach ($expenseData as $exp) {
        if (! empty($exp['id'])) {
            $expense = $document->expenses()->find($exp['id']);
            if ($expense) {
                $expense->update([
                    'name'   => $exp['name']   ?? '',
                    'amount' => $exp['amount'] ?? 0,
                ]);
                $existingExpIds[] = $expense->id;
            }
        } else {
            $newExp = $document->expenses()->create([
                'name'   => $exp['name']   ?? '',
                'amount' => $exp['amount'] ?? 0,
            ]);
            $existingExpIds[] = $newExp->id;
        }
    }
    // delete old expenses not in request
    $document->expenses()
        ->whereNotIn('id', $existingExpIds)
        ->delete();

    // 7) DELETE the Document if there are no items left
    $newCount = $document->documentItems()->count();
    if ($newCount === 0) {
        $document->delete();
        return response()->json([
            'success' => true,
            'deleted' => true,
            'message' => 'Document had no items, so it was deleted.'
        ]);
    }

    // If we still have items, we keep the doc
    return response()->json([
        'success' => true,
        'deleted' => false,
        'message' => 'Document updated.',
        'document' => $document->fresh(['documentItems','expenses']),
    ]);
}


// mobile update income
public function updateMobileIncome(Request $request, $id)
    {
        // 1) Find the Document (type 'income' or whichever)
        $document = Document::findOrFail($id);

        // 2) Validate the header fields from the request
        $data = $request->validate([
            'provider_id'           => 'nullable|integer',
            'document_date'         => 'nullable|date',
            'assigned_warehouse_id' => 'nullable|integer',
            // etc.
        ]);

        DB::beginTransaction();

        try {
            // 3) Update the Document “header”
            $document->update([
                'provider_id'     => $data['provider_id'] ?? null,
                'document_date'   => $data['document_date'] ?? now(),
                'to_warehouse_id' => $data['assigned_warehouse_id'] ?? null,
                // Possibly keep document_type_id if needed
            ]);

            $warehouseId = $document->to_warehouse_id;

            // 4) Revert old items from warehouse (if you want to fully re-apply them)
            $oldItems = $document->documentItems;
            if ($warehouseId) {
                foreach ($oldItems as $oldItem) {
                    $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                        ->where('product_subcard_id', $oldItem->product_subcard_id)
                        ->where('unit_measurement', $oldItem->unit_measurement)
                        ->first();

                    if ($whItem) {
                        $whItem->quantity -= $oldItem->quantity;
                        $whItem->brutto   -= $oldItem->brutto;
                        $whItem->netto    -= $oldItem->netto;
                        // etc. cost_price, total_sum, etc.
                        $whItem->save();
                    }
                }
            }

            // 5) Update or create DocumentItems
            $items = $request->input('products', []);
            $existingIds = [];

            foreach ($items as $itemData) {
                if (!empty($itemData['id'])) {
                    $docItem = $document->documentItems()->find($itemData['id']);
                    if ($docItem) {
                        $docItem->update($itemData);
                        $existingIds[] = $docItem->id;
                    }
                } else {
                    // create new row
                    $newItem = $document->documentItems()->create($itemData);
                    $existingIds[] = $newItem->id;
                }
            }

            // Delete items the user removed
            $document->documentItems()
                ->whereNotIn('id', $existingIds)
                ->delete();

            // 6) Re-apply new items to warehouse
            if ($warehouseId) {
                $updatedItems = $document->documentItems;
                foreach ($updatedItems as $updItem) {
                    $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                        ->where('product_subcard_id', $updItem->product_subcard_id)
                        ->where('unit_measurement', $updItem->unit_measurement)
                        ->first();

                    if (!$whItem) {
                        $whItem = new WarehouseItem();
                        $whItem->warehouse_id       = $warehouseId;
                        $whItem->product_subcard_id = $updItem->product_subcard_id;
                        $whItem->unit_measurement   = $updItem->unit_measurement;
                        $whItem->quantity           = 0;
                        $whItem->brutto             = 0;
                        $whItem->netto              = 0;
                    }

                    $whItem->quantity += $updItem->quantity;
                    $whItem->brutto   += $updItem->brutto;
                    $whItem->netto    += $updItem->netto;
                    // etc. cost_price or total_sum, if you track them
                    $whItem->save();
                }
            }

            // 7) Update or create “Expenses”
            $expenseData = $request->input('expenses', []);
            $existingExpIds = [];
            foreach ($expenseData as $expRow) {
                if (!empty($expRow['id'])) {
                    $expense = $document->expenses()->find($expRow['id']);
                    if ($expense) {
                        $expense->update([
                            'name'   => $expRow['name']   ?? '',
                            'amount' => $expRow['amount'] ?? 0,
                        ]);
                        $existingExpIds[] = $expense->id;
                    }
                } else {
                    // create
                    $newExp = $document->expenses()->create([
                        'name'   => $expRow['name']   ?? '',
                        'amount' => $expRow['amount'] ?? 0,
                    ]);
                    $existingExpIds[] = $newExp->id;
                }
            }
            $document->expenses()
                ->whereNotIn('id', $existingExpIds)
                ->delete();

            // 8) If no items left => delete doc
            $countItems = $document->documentItems()->count();
            if ($countItems === 0) {
                $document->delete();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'deleted' => true,
                    'message' => 'Document had no items, so it was deleted.',
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'deleted' => false,
                'message' => 'Document updated.',
                'document' => $document->fresh(['documentItems','expenses']),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

public function destroy(Document $document)
{
    // (Optional) Revert warehouse if needed. For example, if doc is an "income,"
    // subtract item quantities from the warehouse. If doc is a "sale," add them back, etc.
    // This depends on your business logic. Example for "income" doc:
    if ($document->documentType && $document->documentType->code === 'income') {
        $warehouseId = $document->to_warehouse_id;
        if ($warehouseId) {
            foreach ($document->documentItems as $itm) {
                $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $itm->product_subcard_id)
                    ->where('unit_measurement', $itm->unit_measurement)
                    ->first();
                if ($whItem) {
                    $whItem->quantity -= $itm->quantity;
                    $whItem->brutto   -= $itm->brutto;
                    $whItem->netto    -= $itm->netto;
                    $whItem->save();
                }
            }
        }
    }

    // 1) Delete the doc items first (if no cascade in DB)
    $document->documentItems()->delete();

    // 2) Then delete the doc itself
    $document->delete();

    return response()->json(['success' => true]);
}

}
