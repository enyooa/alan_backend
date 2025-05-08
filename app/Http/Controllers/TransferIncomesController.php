<?php

namespace App\Http\Controllers;
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
use Illuminate\Support\Str;  // ← import Str

use Illuminate\Http\Request;

class TransferIncomesController extends Controller
{
    public function indexTransfers(): JsonResponse
{
    $docs = Document::with([
                'fromWarehouse:id,name',
                'toWarehouse:id,name',
                'items',
                'items.product',
                'items.unitByName',
            ])
            ->whereHas('documentType', fn($q)=>$q->where('code','transfer'))
            ->orderByDesc('document_date')
            ->get();

    return response()->json($docs);
}


/*──────────────────────────────
| B.  Создание перемещения
*──────────────────────────────*/
public function storeTransfer(Request $request): JsonResponse
{
    /* 1.  ВАЛИДАЦИЯ c UUID ─────────────────────────────── */
    $data = $request->validate([
        'from_warehouse_id'                     => ['required','uuid','different:to_warehouse_id','exists:warehouses,id'],
        'to_warehouse_id'                       => ['required','uuid','exists:warehouses,id'],
        'docDate'                               => ['required','date'],

        'products'                              => ['required','array','min:1'],
        'products.*.product.product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.unit.id'                    => ['required','uuid','exists:unit_measurements,id'],
        'products.*.qty'                        => ['required','numeric','gt:0'],
    ]);

    /* 2.  Шорткаты */
    $srcId = $data['from_warehouse_id'];   // uuid
    $dstId = $data['to_warehouse_id'];     // uuid
    $date  = Carbon::parse($data['docDate'])->toDateString();
    $rows  = $data['products'];
    $orgId = $request->user()->organization_id;   // 👈

    DB::beginTransaction();
    try {
        /* 3-A. «Шапка» */
        $type = DocumentType::where('code','transfer')->firstOrFail();

        $doc = Document::create([
            'id'                => Str::uuid(),           // если PK-UUID
            'organization_id'   => $orgId,                // 👈
            'document_type_id'  => $type->id,
            'status'            => '-',
            'from_warehouse_id' => $srcId,
            'to_warehouse_id'   => $dstId,
            'document_date'     => $date,
            'comments'          => "Перемещение $srcId → $dstId",
        ]);

        /* 3-B. Строки + движение остатков */
        foreach ($rows as $row) {
            $prodId   = data_get($row,'product.product_subcard_id');
            $unitId   = data_get($row,'unit.id');
            $unitName = Unit_measurement::findOrFail($unitId)->name;
            $qty      = (float) $row['qty'];

            /* склад-источник */
            $src = WarehouseItem::where([
                        'warehouse_id'       => $srcId,
                        'product_subcard_id' => $prodId,
                        'unit_measurement'   => $unitName,
                    ])->first();

            throw_if(!$src, \Exception::class, "Товар $prodId отсутствует на складе-источнике");
            throw_if($src->quantity < $qty,
                     \Exception::class,
                     "Недостаточно остатка ($prodId / $unitName)");

            /* пропорции */
            $ratio   = $qty / $src->quantity;
            $brutOut = round($src->brutto * $ratio, 2);
            $netOut  = round($src->netto  * $ratio, 2);

            /* списываем со SRC */
            $src->decrementEach([
                'quantity' => $qty,
                'brutto'   => $brutOut,
                'netto'    => $netOut,
            ]);

            /* приходим на DST */
            $dst = WarehouseItem::firstOrCreate(
                ['warehouse_id'=>$dstId,'product_subcard_id'=>$prodId,'unit_measurement'=>$unitName],
                ['quantity'=>0,'brutto'=>0,'netto'=>0]
            );
            $dst->incrementEach([
                'quantity' => $qty,
                'brutto'   => $brutOut,
                'netto'    => $netOut,
            ]);

            /* строка документа хранит остаток SRC-склада */
            DocumentItem::create([
                'id'                  => Str::uuid(),
                'document_id'         => $doc->id,
                'product_subcard_id'  => $prodId,
                'unit_measurement'    => $unitName,
                'quantity'            => $src->quantity,
                'brutto'              => $src->brutto,
                'netto'               => $src->netto,
                'net_unit_weight'     => $src->quantity>0 ? round($src->netto/$src->quantity,4) : 0,
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Transfer saved',
            'doc_id'  => $doc->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('storeTransfer', ['error'=>$e->getMessage()]);
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}



/*──────────────────────────────
| C.  Обновление перемещения
*──────────────────────────────*/
/**
 * PUT /transfer-products/{document}
 * Тело запроса ➜ тот же JSON, что и для POST (см. storeTransfer)
 */
public function updateTransfer(Request $request, Document $document): JsonResponse
{
    /* 0. убеждаемся, что документ — именно «перемещение» */
    if (!$document->documentType || $document->documentType->code !== 'transfer') {
        return response()->json(['error' => 'Not a transfer document'], 422);
    }

    /* 1. валидация входных данных точно по payload React-Native */
    $data = $request->validate([
        'from_warehouse_id'                        => ['required','uuid','different:to_warehouse_id','exists:warehouses,id'],
        'to_warehouse_id'                          => ['required','uuid','exists:warehouses,id'],
        'docDate'                                  => ['required','date'],
        'products'                                 => ['required','array','min:1'],

        // вложенные объекты
        'products.*.product.product_subcard_id'    => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.unit.id'                       => ['required','uuid','exists:unit_measurements,id'],
        'products.*.qty'                           => ['required','numeric'],
    ]);

    /* 2. шорткаты */
    $srcId = $data['from_warehouse_id'];
    $dstId = $data['to_warehouse_id'];
    $date  = Carbon::parse($data['docDate'])->toDateString();
    $rows  = $data['products'];

    DB::beginTransaction();
    try {
        /* ─────────────────────────────────────────────────────
         * A.   ОТКАТЫВАЕМ СТАРОЕ перемещение
         *─────────────────────────────────────────────────────*/
        foreach ($document->items as $old) {
            // a) вернём остаток на склад-источник
            $src = WarehouseItem::firstOrCreate(
                       ['warehouse_id'       => $document->from_warehouse_id,
                        'product_subcard_id' => $old->product_subcard_id,
                        'unit_measurement'   => $old->unit_measurement],
                       ['quantity'=>0,'brutto'=>0,'netto'=>0]
                   );
            $src->quantity += $old->quantity;
            $src->brutto   += $old->brutto;
            $src->netto    += $old->netto;
            $src->save();

            // b) вычтем тот же объём со склада-получателя
            $dst = WarehouseItem::where([
                      'warehouse_id'       => $document->to_warehouse_id,
                      'product_subcard_id' => $old->product_subcard_id,
                      'unit_measurement'   => $old->unit_measurement,
                  ])->first();
            if ($dst) {
                $dst->quantity -= $old->quantity;
                $dst->brutto   -= $old->brutto;
                $dst->netto    -= $old->netto;
                $dst->save();
            }
        }

        /* c) удаляем старые строки */
        $document->items()->delete();

        /* ─────────────────────────────────────────────────────
         * B.  ПРИМЕНЯЕМ НОВЫЕ строки (логика = storeTransfer)
         *─────────────────────────────────────────────────────*/
        foreach ($rows as $row) {

            $prodId   = data_get($row,'product.product_subcard_id');
            $unitId   = data_get($row,'unit.id');
            $unitName = Unit_measurement::findOrFail($unitId)->name;
            $qty      = (float)$row['qty'];

            /* — списываем с нового source склада — */
            $src = WarehouseItem::where([
                       'warehouse_id'       => $srcId,
                       'product_subcard_id' => $prodId,
                       'unit_measurement'   => $unitName,
                   ])->first();

            if (!$src || $src->quantity < $qty) {
                throw new \Exception("Недостаточно товара ID=$prodId ($unitName) на складе-источнике.");
            }

            $ratio      = $qty / $src->quantity;
            $brutOut    = round($src->brutto * $ratio,2);
            $netOut     = round($src->netto  * $ratio,2);

            $src->quantity -= $qty;
            $src->brutto   -= $brutOut;
            $src->netto    -= $netOut;
            $src->save();

            /* — добавляем на новый dest склад — */
            $dst = WarehouseItem::firstOrCreate(
                     ['warehouse_id'=>$dstId,'product_subcard_id'=>$prodId,'unit_measurement'=>$unitName],
                     ['quantity'=>0,'brutto'=>0,'netto'=>0]
                   );
            $dst->quantity += $qty;
            $dst->brutto   += $brutOut;
            $dst->netto    += $netOut;
            $dst->save();

            /* — строка документа хранит остаток на SRC — */
            DocumentItem::create([
                'document_id'        => $document->id,
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
                'quantity'           => $src->quantity,
                'brutto'             => $src->brutto,
                'netto'              => $src->netto,
                'net_unit_weight'    => $src->quantity>0 ? round($src->netto/$src->quantity,4) : 0,
            ]);
        }

        /* ─────────────────────────────────────────────────────
         * C.  Обновляем «шапку»
         *─────────────────────────────────────────────────────*/
        $document->update([
            'from_warehouse_id' => $srcId,
            'to_warehouse_id'   => $dstId,
            'document_date'     => $date,
            'comments'          => "Перемещение $srcId → $dstId (обновлено)",
        ]);

        DB::commit();
        return response()->json(['success'=>true,'message'=>'Transfer updated'],200);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateTransfer error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}


/*──────────────────────────────
| D.  Удаление перемещения
*──────────────────────────────*/
public function destroyTransfer(Document $document): JsonResponse
{
    if (!$document->documentType || $document->documentType->code !== 'transfer') {
        return response()->json(['error'=>'Not a transfer document'],422);
    }

    DB::beginTransaction();
    try {
        foreach ($document->items as $row) {
            $movedQty = $row->quantity_before_move = ($row->quantity ?? 0)   // сколько переместили
                       ? $row->quantity_before_move = $row->quantity_before_move ?? 0
                       : 0;

            /* вернуть на SRC */
            $src = WarehouseItem::firstOrCreate(
                    ['warehouse_id'=>$document->from_warehouse_id,
                     'product_subcard_id'=>$row->product_subcard_id,
                     'unit_measurement'=>$row->unit_measurement],
                    ['quantity'=>0,'brutto'=>0,'netto'=>0]);
            $src->quantity += $movedQty;
            $src->save();

            /* списать с DST */
            $dst = WarehouseItem::where([
                    'warehouse_id'=>$document->to_warehouse_id,
                    'product_subcard_id'=>$row->product_subcard_id,
                    'unit_measurement'=>$row->unit_measurement])->first();
            if ($dst) {
                $dst->quantity -= $movedQty;
                $dst->save();
            }
        }

        $document->items()->delete();
        $document->delete();

        DB::commit();
        return response()->json(['success'=>true,'message'=>'Transfer deleted'],200);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('destroyTransfer error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}
}
