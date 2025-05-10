<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Document,
    DocumentItem,
    DocumentType,
    Unit_measurement,
    WarehouseItem
};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WriteoffIncomesController extends Controller
{
    /*════════════ 1. LIST ════════════*/
    public function indexWriteOff()
    {
        $docs = Document::with([
                    'fromWarehouse:id,name',
                    'items',
                    'items.product',
                    'items.unitByName',   // связь unit_measurement = name
                ])
                ->whereHas('documentType', fn ($q) => $q->where('code', 'write_off'))
                ->orderByDesc('document_date')
                ->get();

        return $docs;            // Laravel сам превратит массив/коллекцию в JSON
    }

    /*════════════ 2. STORE ═══════════*/
    public function postWriteOff(Request $request)
    {
        /* 1. VALIDATION ------------------------------------------------ */
        $v = $request->validate([
            'docDate'               => ['required', 'date'],
            'assigned_warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],

            'products'                                      => ['required', 'array', 'min:1'],
            'products.*.product.product_subcard_id'         => ['required', 'uuid', 'exists:product_sub_cards,id'],
            'products.*.unit.name'                          => ['required', 'string', 'max:50'],

            'products.*.qty'        => ['required', 'numeric', 'gt:0'],
            'products.*.brutto'     => ['nullable', 'numeric', 'gte:0'],
            'products.*.netto'      => ['nullable', 'numeric', 'gte:0'],
            'products.*.price'      => ['nullable', 'numeric', 'gte:0'],
            'products.*.total_sum'  => ['nullable', 'numeric', 'gte:0'],

            'comments'              => ['nullable', 'string', 'max:1000'],
        ]);

        /* 2. SHORTCUTS ------------------------------------------------- */
        $rows    = $v['products'];
        $whId    = $v['assigned_warehouse_id'];
        $docDate = Carbon::parse($v['docDate'])->toDateString();
        $orgId   = $request->user()->organization_id;

        /* 3. TRANSACTION ---------------------------------------------- */
        DB::beginTransaction();
        try {
            /* Header */
            $type = DocumentType::where('code', 'write_off')->firstOrFail();

            $doc = Document::create([
                'id'                => (string) Str::uuid(),
                'organization_id'   => $orgId,
                'document_type_id'  => $type->id,
                'status'            => '-',
                'from_warehouse_id' => $whId,
                'document_date'     => $docDate,
                'comments'          => $request->input('comments', ''),
            ]);

            /* Rows + stock */
            $batch = [];
            foreach ($rows as $row) {
                $prodId   = data_get($row, 'product.product_subcard_id');
                $unitName = data_get($row, 'unit.name');
                $qty      = (float) $row['qty'];

                $stock = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $prodId,
                            'unit_measurement'   => $unitName,
                        ])->first();

                throw_if(!$stock, \Exception::class, "Товар $prodId отсутствует на складе");
                throw_if($stock->quantity < $qty, \Exception::class,
                         "Недостаточно остатка ($prodId / $unitName)");

                $ratio = $qty / $stock->quantity;
                $wBr   = round($stock->brutto  * $ratio, 2);
                $wNt   = round($stock->netto   * $ratio, 2);
                $wSum  = round($stock->total_sum           * $ratio, 2);
                $wAdd  = round($stock->additional_expenses * $ratio, 2);

                $stock->update([
                    'quantity'            => $stock->quantity - $qty,
                    'brutto'              => $stock->brutto   - $wBr,
                    'netto'               => $stock->netto    - $wNt,
                    'total_sum'           => $stock->total_sum - $wSum,
                    'additional_expenses' => $stock->additional_expenses - $wAdd,
                ]);

                $batch[] = [
                    'id'                  => (string) Str::uuid(),
                    'document_id'         => $doc->id,
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $unitName,
                    'quantity'            => $qty,
                    'brutto'              => $wBr,
                    'netto'               => $wNt,
                    'price'               => $row['price'] ?? $stock->price ?? 0,
                    'total_sum'           => $wSum,
                    'cost_price'          => $stock->cost_price ?? 0,
                    'additional_expenses' => $wAdd,
                    'net_unit_weight'     => $qty > 0 ? round($wNt / $qty, 4) : 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }
            DocumentItem::insert($batch);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Write-off saved',
                'doc_id'  => $doc->id,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('postWriteOff: '.$e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /*════════════ 3. UPDATE ══════════*/
    public function updateWriteOff(Request $request, Document $document)
    {
        Log::info($request->all());

        /* 0. Убедимся, что это документ-списание */
        $document->load('documentType', 'items');
        if ($document->documentType->code !== 'write_off') {
            return ['success' => false, 'error' => 'Not a write-off document'];
        }

        /* 1. Валидация входящих данных */
        $rules = [
            'assigned_warehouse_id'                 => ['required','uuid','exists:warehouses,id'],
            'docDate'                               => ['required','date'],

            'products'                              => ['required','array','min:1'],
            'products.*.product.product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],

            /* единица измерения может прийти
               – либо в объекте unit.name
               – либо как product.unit_measurement      */
            'products.*.unit.name'                  => ['nullable','string','exists:unit_measurements,name'],
            'products.*.product.unit_measurement'   => ['nullable','string','exists:unit_measurements,name'],

            'products.*.qty'        => ['required','numeric','gt:0'],
            'products.*.brutto'     => ['nullable','numeric','gte:0'],
            'products.*.netto'      => ['nullable','numeric','gte:0'],
            'products.*.price'      => ['nullable','numeric','gte:0'],
            'products.*.total_sum'  => ['nullable','numeric','gte:0'],

            'comments'              => ['nullable','string','max:1000'],
        ];

        /* добавляем after-hook: хотя бы один источник unit-строки обязан быть */
        $v = validator($request->all(), $rules);
        $v->after(function ($validator) {
            foreach ($validator->getData()['products'] as $idx => $row) {
                if (
                    empty(data_get($row, 'unit.name')) &&
                    empty(data_get($row, 'product.unit_measurement'))
                ) {
                    $validator->errors()->add(
                        "products.$idx.unit",
                        'Нужно указать единицу измерения (unit.name или product.unit_measurement)'
                    );
                }
            }
        });
        $v->validate();                     // бросит ValidationException при ошибке
        $payload = $v->validated();

        /* 2. Шорткаты */
        $whId  = $payload['assigned_warehouse_id'];
        $rows  = $payload['products'];
        $date  = Carbon::parse($payload['docDate'])->toDateString();

        DB::beginTransaction();
        try {
            /* ── 1. Откатываем старые списания ─────────────────────────── */
            foreach ($document->items as $old) {
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $document->from_warehouse_id,
                            'product_subcard_id' => $old->product_subcard_id,
                            'unit_measurement'   => $old->unit_measurement,
                        ])->first();

                if ($stock) {
                    $stock->quantity            += $old->quantity;
                    $stock->brutto              += $old->brutto;
                    $stock->netto               += $old->netto;
                    $stock->total_sum           += $old->total_sum;
                    $stock->additional_expenses += $old->additional_expenses;
                    $stock->save();
                }
            }
            $document->items()->delete();

            /* ── 2. Формируем новые строки ─────────────────────────────── */
            $batch = [];
            foreach ($rows as $row) {
                $prodId   = data_get($row, 'product.product_subcard_id');
                $unitName = data_get($row, 'unit.name')
                           ?: data_get($row, 'product.unit_measurement'); // ← ключевая строка
                $qty      = (float) $row['qty'];

                $stock = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $prodId,
                            'unit_measurement'   => $unitName,
                        ])->first();

                throw_if(!$stock || $stock->quantity < $qty,
                         \Exception::class,
                         "Недостаточно остатка ($prodId / $unitName)");

                $ratio = $qty / $stock->quantity;
                $wBr   = round($stock->brutto  * $ratio, 2);
                $wNt   = round($stock->netto   * $ratio, 2);
                $wSum  = round($stock->total_sum * $ratio, 2);
                $wAdd  = round($stock->additional_expenses * $ratio, 2);

                $stock->update([
                    'quantity'            => $stock->quantity - $qty,
                    'brutto'              => $stock->brutto   - $wBr,
                    'netto'               => $stock->netto    - $wNt,
                    'total_sum'           => $stock->total_sum - $wSum,
                    'additional_expenses' => $stock->additional_expenses - $wAdd,
                ]);

                $batch[] = [
                    'id'                  => (string) Str::uuid(),
                    'document_id'         => $document->id,
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $unitName,
                    'quantity'            => $qty,
                    'brutto'              => $wBr,
                    'netto'               => $wNt,
                    'price'               => $row['price'] ?? $stock->price ?? 0,
                    'total_sum'           => $wSum,
                    'cost_price'          => $stock->cost_price ?? 0,
                    'additional_expenses' => $wAdd,
                    'net_unit_weight'     => $qty > 0 ? round($wNt / $qty, 4) : 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }
            DocumentItem::insert($batch);

            /* ── 3. Шапка ──────────────────────────────────────────────── */
            $document->update([
                'from_warehouse_id' => $whId,
                'document_date'     => $date,
                'comments'          => $request->input('comments', ''),
            ]);

            DB::commit();
            return ['success' => true, 'message' => 'Write-off updated'];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('updateWriteOff: '.$e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /*════════════ 4. DELETE ══════════*/
    public function deleteWriteOff(Document $document)
    {
        $document->load('documentType', 'items');
        if ($document->documentType->code !== 'write_off') {
            return ['success' => false, 'error' => 'Not a write-off document'];
        }

        DB::beginTransaction();
        try {
            foreach ($document->items as $row) {
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $document->from_warehouse_id,
                            'product_subcard_id' => $row->product_subcard_id,
                            'unit_measurement'   => $row->unit_measurement,
                        ])->first();

                if ($stock) {
                    $stock->quantity            += $row->quantity;
                    $stock->brutto              += $row->brutto;
                    $stock->netto               += $row->netto;
                    $stock->total_sum           += $row->total_sum;
                    $stock->additional_expenses += $row->additional_expenses;
                    $stock->save();
                }
            }

            $document->items()->delete();
            $document->delete();

            DB::commit();
            return ['success' => true, 'message' => 'Write-off deleted'];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('deleteWriteOff: '.$e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
