<?php
/*───────────────────────────────────────────────────────────────
 |  Инвентаризация (document_type.code = 'inventory')
 |  Создаёт только Document и DocumentItems,
 |  **НЕ** трогает таблицу warehouse_items.
 |  Совместимо с PHP 7.4 / Laravel 8.
 *──────────────────────────────────────────────────────────────*/

namespace App\Http\Controllers;

use App\Models\{
    Document,
    DocumentItem,
    DocumentType
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /*════════════ 1. LIST ════════════*/
    public function index(Request $request): JsonResponse
    {
        $docs = Document::with([
                    'fromWarehouse:id,name',
                    'items',
                    'items.product',
                    'items.unit',   // связь unit_measurement = name
                ])
                ->whereHas('documentType', function ($q) {
                    $q->where('code', 'inventory');
                })
                ->when(
                    $request->filled('warehouse_id'),
                    fn ($q) => $q->where('from_warehouse_id', $request->query('warehouse_id'))
                )
                ->orderByDesc('document_date')
                ->paginate(25)
                ->appends($request->query());

        return response()->json($docs);
    }

    /*════════════ 2. SHOW ════════════*/
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

    /*════════════ 3. STORE ═══════════*/
    public function store(Request $request): JsonResponse
    {
        try   { $data = $this->validatePayload($request); }
        catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $doc = $this->createInventoryDoc($data);
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

    /*════════════ 4. UPDATE ══════════*/
    public function update(Request $request, Document $document): JsonResponse
    {
        $document->load('documentType');
        if ($document->documentType->code !== 'inventory') {
            return response()->json(['success' => false, 'error' => 'Not an inventory document'], 400);
        }

        try   { $data = $this->validatePayload($request); }
        catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 1. удаляем старые строки
            $document->items()->delete();

            // 2. обновляем шапку
            $document->update([
                'from_warehouse_id' => $data['warehouse_id'],
                'document_date'     => Carbon::now()->toDateString(),
                'comments'          => $data['comments'] ?? '',
            ]);

            // 3. создаём новые строки
            $this->insertItems($document, $data['products']);

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

    /*════════════ 5. DESTROY ═════════*/
    public function destroy(Document $document): JsonResponse
    {
        $document->load('documentType');
        if ($document->documentType->code !== 'inventory') {
            return response()->json(['success' => false, 'error' => 'Not an inventory document'], 400);
        }

        DB::beginTransaction();
        try {
            $document->items()->delete();
            $document->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Документ удалён']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*════════════ HELPERS ════════════*/

    /** Валидация */
    protected function validatePayload(Request $r): array
    {
        return $r->validate([
            'warehouse_id'                          => ['required','uuid','exists:warehouses,id'],

            'products'                              => ['required','array','min:1'],
            'products.*.product.product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.name'                  => ['required','string','exists:unit_measurements,name'],
            'products.*.qty'                        => ['required','numeric','gte:0'],

            'comments'                              => ['nullable','string','max:1000'],
        ]);
    }

    /** Создать документ и строки */
    protected function createInventoryDoc(array $data): Document
    {
        $docType = DocumentType::where('code', 'inventory')->firstOrFail();

        $doc = Document::create([
            'document_type_id'  => $docType->id,
            'status'            => '±',
            'from_warehouse_id' => $data['warehouse_id'],
            'document_date'     => Carbon::now()->toDateString(),
            'comments'          => $data['comments'] ?? '',
        ]);

        $this->insertItems($doc, $data['products']);

        return $doc;
    }

    /** Вставка строк в document_items (bulk insert с UUID) */
    protected function insertItems(Document $doc, array $products): void
    {
        $batch = [];
        $now   = Carbon::now();

        foreach ($products as $row) {
            $batch[] = [
                'id'                  => (string) Str::uuid(),
                'document_id'         => $doc->id,
                'product_subcard_id'  => $row['product']['product_subcard_id'],
                'unit_measurement'    => $row['unit']['name'],
                'quantity'            => (float) $row['qty'],
                'brutto'              => 0,
                'netto'               => 0,
                'price'               => 0,
                'total_sum'           => 0,
                'cost_price'          => 0,
                'additional_expenses' => 0,
                'net_unit_weight'     => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        DocumentItem::insert($batch);
    }

    protected function fail(\Throwable $e): JsonResponse
    {
        Log::error('Inventory error: '.$e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
