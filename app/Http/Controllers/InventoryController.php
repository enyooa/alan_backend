<?php
/*───────────────────────────────────────────────────────────────
 |  Инвентаризация  (document_type.code = 'inventory')
 |  REST-набор:  index / show / store / update / destroy
 *──────────────────────────────────────────────────────────────*/

namespace App\Http\Controllers;

use App\Models\{
    Document,
    DocumentItem,
    DocumentType,
    ProductSubCard,
    Unit_measurement,
    WarehouseItem
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /*═════════════════════ 1. LIST ═════════════════════*/
    public function index(Request $request): JsonResponse
    {
        Log::info($request->all());

        $docs = Document::with([
                    'fromWarehouse:id,name',

                    // строки + вложенные объекты
                    'items',               // сами строки
                    'items.product',       // ProductSubCard
                    'items.unit',          // Unit_measurement (по name)
                ])
                ->whereHas('documentType', fn ($q) => $q->where('code', 'inventory'))
                ->when(
                    $request->filled('warehouse_id'),
                    fn ($q) => $q->where('from_warehouse_id', (int) $request->query('warehouse_id'))
                )
                ->orderByDesc('document_date')
                ->paginate(25)
                ->appends($request->query());

        return response()->json($docs);
    }

    /*═════════════════════ 2. SHOW ═════════════════════*/
    public function show(Document $document): JsonResponse
    {
        $document->load([
            'fromWarehouse:id,name',
            'items.product',
            'items.unit',
        ]);

        if ($document->documentType->code !== 'inventory') {
            return response()->json(['success' => false, 'error' => 'Not an inventory document'], 400);
        }

        return response()->json($document);
    }

    /*═════════════════════ 3. STORE ════════════════════*/
    public function store(Request $request): JsonResponse
    {
        Log::info($request->all());

        try   { $data = $this->validatePayload($request); }       // create-mode
        catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $doc = $this->insertInventory($data);                 // header + rows
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Инвентаризация создана',
                'doc_id'  => $doc->id,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*═════════════════════ 4. UPDATE ═══════════════════*/
    public function update(Request $request, Document $document): JsonResponse
    {
        $document->load('documentType');
        if ($document->documentType->code !== 'inventory') {
            return response()->json(['success' => false, 'error' => 'Not an inventory document'], 400);
        }

        try   { $data = $this->validatePayload($request, $document->id); }  // update-mode
        catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            /* 1. откатываем прежнее влияние */
            $this->rollbackInventory($document);

            /* 2. удаляем старые строки */
            $document->items()->delete();

            /* 3. обновляем шапку */
            $document->update([
                'from_warehouse_id' => $data['warehouse_id'],
                'document_date'     => now()->toDateString(),
                'comments'          => $request->input('comments', ''),
            ]);

            /* 4. вставляем новые строки */
            $this->insertInventory($data, $document);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Инвентаризация обновлена',
                'doc_id'  => $document->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*═════════════════════ 5. DESTROY ══════════════════*/
    public function destroy(Document $document): JsonResponse
    {
        $document->load('documentType', 'items');
        if ($document->documentType->code !== 'inventory') {
            return response()->json(['success' => false, 'error' => 'Not an inventory document'], 400);
        }

        DB::beginTransaction();
        try {
            $this->rollbackInventory($document);
            $document->items()->delete();
            $document->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Документ удалён']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*═════════════════════ HELPERS ═════════════════════*/

    /** Единая валидация */
    protected function validatePayload(Request $r, ?int $docId = null): array
    {
        return $r->validate([
            'warehouse_id'                          => ['required','uuid','exists:warehouses,id'],

            'products'                              => ['required','array','min:1'],
            'products.*.product.product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.id'                    => ['required','uuid','exists:unit_measurements,id'],
            'products.*.qty'                        => ['required','numeric','gte:0'],
        ]);
    }

    /** Создаёт документ или добавляет строки к существующему */
    protected function insertInventory(array $data, ?Document $existing = null): Document
    {
        /* 1. Шапка */
        $docType = DocumentType::where('code', 'inventory')->firstOrFail();

        $doc = $existing ?? Document::create([
            'document_type_id'  => $docType->id,
            'status'            => '±',
            'from_warehouse_id' => $data['warehouse_id'],
            'document_date'     => now()->toDateString(),
            'comments'          => $data['comments'] ?? '',
        ]);

        /* 2. Строки */
        $whId  = $data['warehouse_id'];
        $batch = [];

        foreach ($data['products'] as $row) {

            $prodId   = (int) $row['product']['product_subcard_id'];
            $unitId   = (int) $row['unit']['id'];
            $unitName = Unit_measurement::findOrFail($unitId)->name;
            $counted  = (float) $row['qty'];

            /* остаток до инвентаризации */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $prodId,
                        'unit_measurement'   => $unitName,
                    ])->first();

            $oldQty = $stock->quantity ?? 0;
            $delta  = $counted - $oldQty;

            if ($stock) {
                $stock->quantity += $delta;
                $stock->save();
            } else {
                $stock = WarehouseItem::create([
                    'warehouse_id'       => $whId,
                    'product_subcard_id' => $prodId,
                    'unit_measurement'   => $unitName,
                    'quantity'           => $counted,
                ]);
            }

            $batch[] = [
                'document_id'        => $doc->id,
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
                'quantity'           => $counted,
                'brutto'             => 0,
                'netto'              => 0,
                'price'              => 0,
                'total_sum'          => 0,
                'cost_price'         => 0,
                'additional_expenses'=> 0,
                'net_unit_weight'    => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        DocumentItem::insert($batch);
        return $doc;
    }

    /** Откат изменений, внесённых документом */
    protected function rollbackInventory(Document $doc): void
    {
        $whId = $doc->from_warehouse_id;

        foreach ($doc->items as $row) {
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $row->product_subcard_id,
                        'unit_measurement'   => $row->unit_measurement,
                    ])->first();

            if ($stock) {
                $stock->quantity = $row->quantity;
                $stock->save();
            }
        }
    }

    protected function fail(\Throwable $e): JsonResponse
    {
        Log::error('Inventory error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}
