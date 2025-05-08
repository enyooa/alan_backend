<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Unit_measurement;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
class SalesIncomesController extends Controller
{
    public function indexSales(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $docs = Document::with([
                    'fromWarehouse:id,name',
                    'provider:id,name',
                    'items','items.product','items.unitByName',
                    'expenses:id,document_id,name,provider_id','expenses.provider:id,name',
                ])
                ->where('organization_id', $orgId)                     // ← только своя организация
                ->whereHas('documentType', fn ($q) => $q->where('code','sale'))
                ->orderByDesc('document_date')
                ->get();

        return response()->json($docs);
    }


/**
 * POST /api/sales
 * Создаёт документ «Sale» и списывает товар партиями (FIFO).
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
/**
 * POST /api/sales
 * Создаёт документ «Sale» и списывает товар партиями (FIFO), не используя unit-id.
 */
public function postSales(Request $request): JsonResponse
{
    // Log::info($request->all());

    /* ─── 1. Валидация ───────────────────────────────────────────── */
    $v = $request->validate([
        // UUID-ключи
        'client_id'             => ['sometimes','uuid','exists:users,id'],
        // 'to_organization_id'  => ['nullable','uuid','exists:organizations,id','required_without:client_id'],

        'assigned_warehouse_id' => ['required', 'uuid','exists:warehouses,id'],
        'docDate'               => ['nullable', 'date'],

        // товары
        'products'                                            => ['required','array','min:1'],
        'products.*.product.product_subcard_id'               => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.product.unit_measurement'                 => ['required','string','max:32'],

        // количество / цена
        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.price'     => ['required','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['required','numeric'],
    ]);

    /* ─── 2. Шорткаты ────────────────────────────────────────────── */
    $rows   = $v['products'];
    $whId   = $v['assigned_warehouse_id'];
    $client = $v['client_id'] ?? null;
    $date   = \Illuminate\Support\Carbon::parse($v['docDate'] ?? now())->toDateString();

    // убеждаемся, что склад существует
    $warehouse = Warehouse::findOrFail($whId);

    /* ─── 3. Транзакция ──────────────────────────────────────────── */
    DB::beginTransaction();
    try {
        /* 3.1 Шапка документа */
        $doc = Document::create([
            'document_type_id'  => DocumentType::where('code','sale')->firstOrFail()->id,
            'status'            => '-',
            'client_id'         => $client,
            'document_date'     => $date,
            'from_warehouse_id' => $whId,
            'organization_id'   => $request->user()->organization_id,
        ]);

        /* 3.2 Строки + списание FIFO                               */
        foreach ($rows as $row) {

            $prodId   = data_get($row,'product.product_subcard_id');
            $unitName = trim((string) data_get($row,'product.unit_measurement'));

            if ($unitName === '') {
                throw new \Exception("Не указана единица измерения для товара {$prodId}");
            }

            $qtyNeed = (float) ($row['qtyTare'] ?? $row['netto'] ?? 0);
            if ($qtyNeed <= 0) {
                throw new \Exception("Количество должно быть > 0 (товар {$prodId})");
            }

            // партии с остатком
            $batches = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $prodId,
                            'unit_measurement'   => $unitName,
                        ])
                        ->where('quantity','>',0)
                        ->orderBy('created_at')     // FIFO
                        ->lockForUpdate()
                        ->get();

            $qtyLeft = $qtyNeed;

            foreach ($batches as $batch) {
                if ($qtyLeft <= 0) break;

                $take  = min($qtyLeft, $batch->quantity);
                $share = $take / $qtyNeed;

                // строка документа
                DocumentItem::create([
                    'document_id'         => $doc->id,
                    'warehouse_item_id'   => $batch->id,
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $unitName,
                    'quantity'            => $take,
                    'brutto'              => ($row['brutto'] ?? 0) * $share,
                    'netto'               => ($row['netto']  ?? 0) * $share,
                    'price'               => $row['price'],
                    'total_sum'           => $row['price'] * $take,
                    'cost_price'          => ($batch->cost_price ?? 0) * $take,
                ]);

                // списание из партии
                $batch->quantity  -= $take;
                if (isset($row['brutto'])) $batch->brutto -= ($row['brutto'] ?? 0) * $share;
                if (isset($row['netto']))  $batch->netto  -= ($row['netto']  ?? 0) * $share;
                $batch->total_sum  = $batch->price * $batch->quantity;
                $batch->save();

                $qtyLeft -= $take;
            }

            if ($qtyLeft > 0) {
                throw new \Exception("Недостаточно остатка по товару {$prodId} ({$unitName})");
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Продажа сохрнена',
            'doc_id'  => $doc->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('postSales error: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}

/**
 * PUT /sales-products/{document}
 * Body = the same JSON the mobile app sends to POST.
 */
/**
 * PUT /sales-products/{document}
 */
public function updateSales(Request $request, Document $document): JsonResponse
{
    Log::info($request->all);
    /* ─── 0. Проверяем, что это именно документ продажи ───────────── */
    if ($document->documentType->code !== 'sale') {
        return response()->json(['error' => 'Not a sale document'], 422);
    }

    /* ─── 1. Валидация входного JSON ──────────────────────────────── */
    $v = $request->validate([
        // кому продаём (одно из двух обязательно)
        'client_id'            => ['nullable','uuid','exists:users,id','required_without:to_organization_id'],
        'to_organization_id'   => ['nullable','uuid','exists:organizations,id','required_without:client_id'],

        // склад и дата
        'assigned_warehouse_id'=> ['required','uuid','exists:warehouses,id'],
        'docDate'              => ['nullable','date'],

        // товары
        'products'                                            => ['required','array','min:1'],
        'products.*.product.product_subcard_id'               => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.product.unit_measurement'                 => ['required','string','max:32'],

        // количество / цена
        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.price'     => ['required','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['required','numeric'],
    ]);

    /* ─── 2. Шорткаты ─────────────────────────────────────────────── */
    $rows      = $v['products'];
    $whId      = $v['assigned_warehouse_id'];
    $docDate   = \Illuminate\Support\Carbon::parse($v['docDate'] ?? now())->toDateString();
    $receiver  = [
        'client_id'          => $v['client_id']          ?? null,
        'to_organization_id' => $v['to_organization_id'] ?? null,
    ];

    /* ─── 3. Транзакция ───────────────────────────────────────────── */
    DB::beginTransaction();
    try {
        /* 3.1 — Откат старых позиций обратно на склад */
        foreach ($document->items()->lockForUpdate()->get() as $oldItem) {

            // партия, из которой списывали раньше; может быть null, если удалена
            $batch = WarehouseItem::lockForUpdate()->find($oldItem->warehouse_item_id);

            if ($batch) {
                $batch->quantity  += $oldItem->quantity;
                $batch->brutto    += $oldItem->brutto;
                $batch->netto     += $oldItem->netto;
                $batch->total_sum  = $batch->price * $batch->quantity;
                $batch->save();
            }
        }

        // удаляем все старые строки документа
        $document->items()->delete();

        /* 3.2 — Обновляем «шапку» */
        $document->update([
            'client_id'           => $receiver['client_id'],
            'to_organization_id'  => $receiver['to_organization_id'],
            'from_warehouse_id'   => $whId,
            'document_date'       => $docDate,
        ]);

        /* 3.3 — Добавляем новые позиции и списываем партиями FIFO */
        foreach ($rows as $row) {
            $prodId   = data_get($row,'product.product_subcard_id');
            $unitName = trim((string) data_get($row,'product.unit_measurement'));

            if ($unitName === '') {
                throw new \Exception("Не указана единица измерения для товара {$prodId}");
            }

            $qtyNeed = (float) ($row['qtyTare'] ?? $row['netto'] ?? 0);
            if ($qtyNeed <= 0) {
                throw new \Exception("Количество должно быть > 0 (товар {$prodId})");
            }

            // партии с остатком (FIFO)
            $batches = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $prodId,
                            'unit_measurement'   => $unitName,
                        ])
                        ->where('quantity','>',0)
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->get();

            $qtyLeft = $qtyNeed;

            foreach ($batches as $batch) {
                if ($qtyLeft <= 0) break;

                $take  = min($qtyLeft, $batch->quantity);
                $share = $take / $qtyNeed;

                // новая строка документа
                $document->items()->create([
                    'warehouse_item_id'   => $batch->id,
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $unitName,
                    'quantity'            => $take,
                    'brutto'              => ($row['brutto'] ?? 0) * $share,
                    'netto'               => ($row['netto']  ?? 0) * $share,
                    'price'               => $row['price'],
                    'total_sum'           => $row['price'] * $take,
                    'cost_price'          => ($batch->cost_price ?? 0) * $take,
                ]);

                // списываем из партии
                $batch->quantity  -= $take;
                if (isset($row['brutto'])) $batch->brutto -= ($row['brutto'] ?? 0) * $share;
                if (isset($row['netto']))  $batch->netto  -= ($row['netto']  ?? 0) * $share;
                $batch->total_sum  = $batch->price * $batch->quantity;
                $batch->save();

                $qtyLeft -= $take;
            }

            if ($qtyLeft > 0) {
                throw new \Exception("Недостаточно остатка по товару {$prodId} ({$unitName})");
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Sale updated',
            'doc_id'  => $document->id,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateSales error: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}

/**
 * DELETE /sales-products/{document}
 */
public function destroySales(Document $document): JsonResponse
{
    if (!$document->documentType || $document->documentType->code !== 'sale') {
        return response()->json(['error' => 'Not a sale document'], 422);
    }

    DB::beginTransaction();
    try {
        /* 1. вернём остатки на склад */
        foreach ($document->items as $row) {
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $document->from_warehouse_id,
                        'product_subcard_id' => $row->product_subcard_id,
                        'unit_measurement'   => $row->unit_measurement,
                    ])->first();

            if ($stock) {
                $stock->quantity  += $row->quantity;
                $stock->brutto    += $row->brutto;
                $stock->netto     += $row->netto;
                $stock->total_sum += $row->total_sum;
                $stock->save();
            }
        }

        /* 2. удаляем строки, расходы и сам документ */
        $document->items()->delete();
        $document->expenses()->delete();
        $document->delete();

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Sale deleted'], 200);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('destroySales error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}


}
