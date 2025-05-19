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

class TransferIncomesController extends Controller
{
    /*════════════ 1. LIST ════════════*/
    public function indexTransfers()
    {
        return Document::with([
                    'fromWarehouse:id,name',
                    'toWarehouse:id,name',
                    'items',
                    'items.product',
                    'items.unitByName',
                ])
                ->whereHas('documentType', fn ($q) => $q->where('code', 'transfer'))
                ->orderByDesc('document_date')
                ->get();
    }

    /*════════════ 2. STORE ═══════════*/
    public function storeTransfer(Request $request)
    {
        /* 1. VALIDATE */
        $rules = [
            'from_warehouse_id'                     => ['required','uuid','different:to_warehouse_id','exists:warehouses,id'],
            'to_warehouse_id'                       => ['required','uuid','exists:warehouses,id'],
            'docDate'                               => ['required','date'],

            'products'                              => ['required','array','min:1'],
            'products.*.product.product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],

            // единица: можно id, можно name
            'products.*.unit.id'                    => ['nullable','uuid','exists:unit_measurements,id'],
            'products.*.unit.name'                  => ['nullable','string','exists:unit_measurements,name'],

            'products.*.qty'                        => ['required','numeric','gt:0'],
        ];

        $validator = validator($request->all(), $rules);
        $validator->after(function ($v) {
            foreach ($v->getData()['products'] as $i => $row) {
                if (empty(data_get($row,'unit.id')) && empty(data_get($row,'unit.name'))) {
                    $v->errors()->add("products.$i.unit",
                        'Нужно передать либо unit.id, либо unit.name');
                }
            }
        });
        $data = $validator->validate();

        /* 2. SHORTCUTS */
        $srcId = $data['from_warehouse_id'];
        $dstId = $data['to_warehouse_id'];
        $date  = Carbon::parse($data['docDate'])->toDateString();
        $rows  = $data['products'];
        $orgId = $request->user()->organization_id;

        DB::beginTransaction();
        try {
            $docType = DocumentType::where('code','transfer')->firstOrFail();

            $doc = Document::create([
                'id'                => (string) Str::uuid(),
                'organization_id'   => $orgId,
                'document_type_id'  => $docType->id,
                'status'            => '-',
                'from_warehouse_id' => $srcId,
                'to_warehouse_id'   => $dstId,
                'document_date'     => $date,
                'comments'          => "Перемещение $srcId → $dstId",
            ]);

            foreach ($rows as $row) {
                $prodId   = data_get($row,'product.product_subcard_id');
                $unitName = $this->resolveUnitName($row);
                $qty      = (float) $row['qty'];

                $src = WarehouseItem::where([
                           'warehouse_id'       => $srcId,
                           'product_subcard_id' => $prodId,
                           'unit_measurement'   => $unitName,
                       ])->first();

                throw_if(!$src, \Exception::class, "Нет $prodId ($unitName) на складе-источнике");
                throw_if($src->quantity < $qty, \Exception::class,
                         "Недостаточно остатка $prodId ($unitName)");

                $ratio  = $qty / $src->quantity;
                $brMove = round($src->brutto * $ratio, 2);
                $ntMove = round($src->netto  * $ratio, 2);

                $src->update([
                    'quantity' => $src->quantity - $qty,
                    'brutto'   => $src->brutto   - $brMove,
                    'netto'    => $src->netto    - $ntMove,
                ]);

                $dst = WarehouseItem::firstOrCreate(
                         ['warehouse_id'=>$dstId,'product_subcard_id'=>$prodId,'unit_measurement'=>$unitName,'document_id'=>$doc->id],
                         ['quantity'=>0,'brutto'=>0,'netto'=>0]
                       );
                $dst->update([
                    'quantity' => $dst->quantity + $qty,
                    'brutto'   => $dst->brutto   + $brMove,
                    'netto'    => $dst->netto    + $ntMove,
                ]);

                DocumentItem::create([
                    'id'                 => (string) Str::uuid(),
                    'document_id'        => $doc->id,
                    'product_subcard_id' => $prodId,
                    'unit_measurement'   => $unitName,
                    'quantity'           => $src->quantity,
                    'brutto'             => $src->brutto,
                    'netto'              => $src->netto,
                    'net_unit_weight'    => $src->quantity>0 ? round($src->netto/$src->quantity,4) : 0,
                ]);
            }

            DB::commit();
            return ['success'=>true,'message'=>'Transfer saved','doc_id'=>$doc->id];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('storeTransfer: '.$e->getMessage());
            return ['success'=>false,'error'=>$e->getMessage()];
        }
    }

    /*════════════ 3. UPDATE ══════════*/
    /*════════════ 3. UPDATE ══════════*/
public function updateTransfer(Request $request, Document $document)
{
    /* ── 0. Проверяем, что это именно документ-перемещения ─────────── */
    $document->load('documentType', 'items');
    if ($document->documentType->code !== 'transfer') {
        return ['success' => false, 'error' => 'Not a transfer document'];
    }

    /* ── 1. Валидация входных данных ───────────────────────────────── */
    $rules = [
        'from_warehouse_id'                     => ['required', 'uuid', 'different:to_warehouse_id', 'exists:warehouses,id'],
        'to_warehouse_id'                       => ['required', 'uuid', 'exists:warehouses,id'],
        'docDate'                               => ['required', 'date'],

        'products'                              => ['required', 'array', 'min:1'],
        'products.*.product.product_subcard_id' => ['required', 'uuid', 'exists:product_sub_cards,id'],

        /* единица измерения: разрешаем либо id, либо name */
        'products.*.unit.id'                    => ['nullable', 'uuid', 'exists:unit_measurements,id'],
        'products.*.unit.name'                  => ['nullable', 'string', 'exists:unit_measurements,name'],

        'products.*.qty'                        => ['required', 'numeric', 'gt:0'],
    ];

    $validator = validator($request->all(), $rules);
    $validator->after(function ($v) {
        foreach ($v->getData()['products'] as $i => $row) {
            if (empty(data_get($row, 'unit.id')) && empty(data_get($row, 'unit.name'))) {
                $v->errors()->add("products.$i.unit", 'Нужно передать либо unit.id, либо unit.name');
            }
        }
    });
    $data = $validator->validate();

    /* ── 2. Шорткаты ───────────────────────────────────────────────── */
    $srcId = $data['from_warehouse_id'];       // склад-источник
    $dstId = $data['to_warehouse_id'];         // склад-получатель
    $date  = Carbon::parse($data['docDate'])->toDateString();
    $rows  = $data['products'];

    /* ── 3. Транзакция ─────────────────────────────────────────────── */
    DB::beginTransaction();
    try {
        /* A. Откатываем «старые» движения документа */
        foreach ($document->items as $old) {
            $this->returnStock($document->from_warehouse_id, $old); // вернули на источник
            $this->takeFromStock($document->to_warehouse_id,   $old); // забрали с получателя
        }
        $document->items()->delete();   // чистим detail

        /* B. Записываем новые строки перемещения */
        foreach ($rows as $row) {
            $prodId   = data_get($row, 'product.product_subcard_id');
            $unitName = $this->resolveUnitName($row);
            $qtyMove  = (float) $row['qty'];          // сколько реально перемещаем

            /* 1) Проверяем/списываем остаток на источнике */
            $src = WarehouseItem::where([
                       'warehouse_id'       => $srcId,
                       'product_subcard_id' => $prodId,
                       'unit_measurement'   => $unitName,
                   ])->first();

            throw_if(!$src, \Exception::class, "Нет $prodId ($unitName) на складе-источнике");
            throw_if($src->quantity < $qtyMove, \Exception::class,
                     "Недостаточно $prodId ($unitName) — остаток {$src->quantity}, нужно $qtyMove");

            /* Пропорционально вычисляем брутто/нетто, которые «уезжают» */
            $ratio   = $qtyMove / $src->quantity;
            $brMove  = round($src->brutto * $ratio, 2);
            $ntMove  = round($src->netto  * $ratio, 2);

            $src->update([
                'quantity' => $src->quantity - $qtyMove,
                'brutto'   => $src->brutto   - $brMove,
                'netto'    => $src->netto    - $ntMove,
            ]);

            /* 2) Приходуем на склад-получатель */
            $dst = WarehouseItem::firstOrCreate(
                     ['warehouse_id' => $dstId,
                      'product_subcard_id' => $prodId,
                      'unit_measurement' => $unitName],
                     ['quantity' => 0, 'brutto' => 0, 'netto' => 0, 'cost_price' => $src->cost_price]
                   );

            /* Пересчитываем себестоимость методом взвешенной средней */
            $newQty = $dst->quantity + $qtyMove;
            $dstCost = $newQty > 0
                ? round(
                    ($dst->quantity * $dst->cost_price + $qtyMove * $src->cost_price) / $newQty,
                    2
                  )
                : $src->cost_price;

            $dst->update([
                'quantity'   => $newQty,
                'brutto'     => $dst->brutto + $brMove,
                'netto'      => $dst->netto  + $ntMove,
                'cost_price' => $dstCost,
            ]);

            /* 3) Создаём строку документа — записываем именно перемещённые объёмы */
            DocumentItem::create([
                'id'                 => (string) Str::uuid(),
                'document_id'        => $document->id,
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
                'quantity'           => $qtyMove,         // то, что уехало
                'brutto'             => $brMove,
                'netto'              => $ntMove,
                'cost_price'         => $src->cost_price,
                'net_unit_weight'    => $qtyMove > 0 ? round($ntMove / $qtyMove, 4) : 0,
            ]);
        }

        /* C. Обновляем «шапку» документа */
        $document->update([
            'from_warehouse_id' => $srcId,
            'to_warehouse_id'   => $dstId,
            'document_date'     => $date,
            'comments'          => "Перемещение $srcId → $dstId (обновлено)",
        ]);

        DB::commit();
        return ['success' => true, 'message' => 'Transfer updated'];

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateTransfer: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

    /*════════════ 4. DELETE ══════════*/
    public function destroyTransfer(Document $document)
    {
        $document->load('documentType', 'items');
        if ($document->documentType->code !== 'transfer') {
            return ['success'=>false,'error'=>'Not a transfer document'];
        }

        DB::beginTransaction();
        try {
            foreach ($document->items as $row) {
                $this->returnStock($document->from_warehouse_id, $row);
                $this->takeFromStock($document->to_warehouse_id,   $row);
            }

            $document->items()->delete();
            $document->delete();

            DB::commit();
            return ['success'=>true,'message'=>'Transfer deleted'];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('destroyTransfer: '.$e->getMessage());
            return ['success'=>false,'error'=>$e->getMessage()];
        }
    }

    /*════════════ HELPERS ════════════*/
    /** Определяем название единицы измерения из row */
    protected function resolveUnitName(array $row): string
    {
        if ($name = data_get($row,'unit.name')) {
            return $name;
        }
        if ($id = data_get($row,'unit.id')) {
            return Unit_measurement::findOrFail($id)->name;
        }
        throw new \Exception('Единица измерения не указана');
    }

    protected function returnStock($whId, $row): void
    {
        $stock = WarehouseItem::firstOrCreate(
            ['warehouse_id'=>$whId,
             'product_subcard_id'=>$row->product_subcard_id,
             'unit_measurement'=>$row->unit_measurement],
            ['quantity'=>0,'brutto'=>0,'netto'=>0]
        );
        $stock->quantity += $row->quantity;
        $stock->brutto   += $row->brutto;
        $stock->netto    += $row->netto;
        $stock->save();
    }

    protected function takeFromStock($whId, $row): void
    {
        $stock = WarehouseItem::where([
                    'warehouse_id'=>$whId,
                    'product_subcard_id'=>$row->product_subcard_id,
                    'unit_measurement'=>$row->unit_measurement])->first();
        if ($stock) {
            $stock->quantity -= $row->quantity;
            $stock->brutto   -= $row->brutto;
            $stock->netto    -= $row->netto;
            $stock->save();
        }
    }
}
