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
use Illuminate\Support\Str;

class SalesIncomesController extends Controller
{
   public function indexSales(Request $request): JsonResponse
{
    $orgId = $request->user()->organization_id;

    $docs = Document::with([
                'fromWarehouse:id,name',
                'provider:id,name',

                /* товарные позиции */
                'items','items.product','items.unitByName',

                /* расходы — выбираем expense_name_id, а саму строку тянем отдельной связью */
                'expenses:id,document_id,expense_name_id,provider_id,amount',
                'expenses.name:id,name',          // 🔹 название расхода
                'expenses.provider:id,name',      // 🔹 поставщик расхода
            ])
            ->where('organization_id', $orgId)
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
    Log::info($request);
    $v = $request->validate([
        'client_id'             => ['required','uuid'],
        'assigned_warehouse_id' => ['required','uuid','exists:warehouses,id'],
        'docDate'               => ['nullable','date'],
        'products'                                            => ['required','array','min:1'],
        'products.*.product.product_subcard_id'               => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.product.unit_measurement'                 => ['required','string','max:32'],
        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.price'     => ['required','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['required','numeric'],
    ]);

    $rows = $v['products'];
    $whId = $v['assigned_warehouse_id'];

    DB::beginTransaction();
    try {
        /* 1. шапка */
        $doc = Document::create([
            'document_type_id'  => DocumentType::where('code','sale')->firstOrFail()->id,
            'status'            => 'pending',          // ← главное отличие
            'client_id'         => $v['client_id'],
            'document_date'     => Carbon::parse($v['docDate'] ?? now())->toDateString(),
            'from_warehouse_id' => $whId,
            'organization_id'   => $request->user()->organization_id,
        ]);

        /* 2. строки БЕЗ warehouse_item_id */
        foreach ($rows as $row) {
            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => data_get($row,'product.product_subcard_id'),
                'unit_measurement'   => data_get($row,'product.unit_measurement'),
                'quantity'           => $row['qtyTare'] ?? $row['netto'],
                'brutto'             => $row['brutto'] ?? null,
                'netto'              => $row['netto']  ?? null,
                'price'              => $row['price'],
                'total_sum'          => $row['total_sum'],
            ]);
        }

        DB::commit();
        return response()->json(['doc_id'=>$doc->id,'status'=>'pending'],201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('postSales error: '.$e->getMessage());
        return response()->json(['error'=>$e->getMessage()],500);
    }
}
public function confirmSale(Request $request, Document $document): JsonResponse
{
    /* 0. проверки */
    if ($document->documentType->code !== 'sale')
        return response()->json(['error'=>'Not a sale document'],422);
    if ($document->status !== 'pending')
        return response()->json(['error'=>'Already confirmed / canceled'],422);
    if ($document->client_id !== $request->user()->id)
        return response()->json(['error'=>'Not your sale'],403);

    DB::beginTransaction();
    try {
        foreach ($document->items as $item) {

            $qtyNeed = $item->quantity;
            $batches = WarehouseItem::where([
                            'warehouse_id'       => $document->from_warehouse_id,
                            'product_subcard_id' => $item->product_subcard_id,
                            'unit_measurement'   => $item->unit_measurement,
                        ])
                        ->where('quantity','>',0)
                        ->orderBy('created_at')   // FIFO
                        ->lockForUpdate()
                        ->get();

            foreach ($batches as $batch) {
                if ($qtyNeed <= 0) break;

                $take  = min($qtyNeed, $batch->quantity);
                $share = $take / $item->quantity;

                /* связываем строку с партией + себестоимость */
                $item->update([
                    'warehouse_item_id' => $batch->id,
                    // 'cost_price'        => ($batch->cost_price ?? 0),
                ]);

                /* списываем */
                $batch->quantity  -= $take;
                if ($item->brutto) $batch->brutto -= $item->brutto * $share;
                if ($item->netto)  $batch->netto  -= $item->netto  * $share;
                $batch->total_sum  = $batch->price * $batch->quantity;
                $batch->save();

                $qtyNeed -= $take;
            }

            if ($qtyNeed > 0)
                throw new \Exception("Недостаточно остатка по товару {$item->product_subcard_id}");
        }

        $document->status = 'confirmed';
        $document->save();

        DB::commit();
        return response()->json(['success'=>true,'doc_id'=>$document->id,'status'=>'confirmed']);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('confirmSale error: '.$e->getMessage());
        return response()->json(['error'=>$e->getMessage()],500);
    }
}


public function mySales(Request $request): JsonResponse
{
    $sales = Document::with([
                    'items','items.product',
                    'fromWarehouse:id,name',
                    'organization:id,name,address'          // ← новая строка

                ])
                ->whereHas('documentType', fn($q)=>$q->where('code','sale'))
                ->where('client_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->get();

    return response()->json($sales);
}




public function postSalesWeb(Request $request): JsonResponse
{
    $v = $request->validate([
        // получатель
        'client_id'          => ['nullable','uuid','exists:users,id','required_without:to_organization_id'],
        'to_organization_id' => ['nullable','uuid','exists:organizations,id','required_without:client_id'],

        'assigned_warehouse_id' => ['required','uuid','exists:warehouses,id'],
        'docDate'               => ['nullable','date'],

        'products'                                            => ['required','array','min:1'],
        'products.*.product.product_subcard_id'               => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.product.unit_measurement'                 => ['required','string','max:32'],
        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.price'     => ['required','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['required','numeric'],
    ]);

    $rows     = $v['products'];
    $whId     = $v['assigned_warehouse_id'];
    $docDate  = Carbon::parse($v['docDate'] ?? now())->toDateString();

    DB::beginTransaction();
    try {
        /* 1. header (status = pending, no stock touched) */
        $doc = Document::create([
            'id'                 => Str::uuid(),
            'document_type_id'   => DocumentType::where('code','sale')->firstOrFail()->id,
            'status'             => 'pending',
            'client_id'          => $v['client_id']          ?? null,
            'to_organization_id' => $v['to_organization_id'] ?? null,
            'document_date'      => $docDate,
            'from_warehouse_id'  => $whId,
            'organization_id'    => $request->user()->organization_id,
        ]);

        /* 2. rows WITHOUT warehouse_item_id / cost_price */
        foreach ($rows as $row) {
            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => data_get($row,'product.product_subcard_id'),
                'unit_measurement'   => trim(data_get($row,'product.unit_measurement')),
                'quantity'           => $row['qtyTare'] ?? $row['netto'],
                'brutto'             => $row['brutto'] ?? null,
                'netto'              => $row['netto']  ?? null,
                'price'              => $row['price'],
                'total_sum'          => $row['total_sum'],
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'doc_id'  => $doc->id,
            'status'  => 'pending',
            'message' => 'Sale saved; waiting for client confirmation',
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('postSalesWeb error: '.$e->getMessage());
        return response()->json(['error'=>$e->getMessage()],500);
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
                    'cost_price'          => ($batch->cost_price ?? 0),
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
