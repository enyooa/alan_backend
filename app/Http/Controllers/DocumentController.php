<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentItem;
use App\Models\ReferenceItem;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{

    // app/Http/Controllers/DocumentController.php
// app/Http/Controllers/DocumentController.php
public function indexIncomes()
{
    $docs = Document::with([
            /* склад назначения */
            'toWarehouse:id,name',

            /* поставщик документа */
            'providerItem.reference',

            /* товарные позиции */
            'items.product.reference',
            'items.unitRef',

            /* расходы */
            'expenses.referenceItem.reference',   // статья расхода
            'expenses.providerItem.reference',    // ⬅︎ НОВОЕ!
        ])
        ->whereHas('documentType', fn ($q) => $q->where('code', 'income'))
        ->orderByDesc('document_date')
        ->get();

    return response()->json($docs);
}
public function indexSales(): \Illuminate\Http\JsonResponse
{
    $docs = Document::with([
            /* склады */
            'fromWarehouse:id,name',
            'toWarehouse:id,name',

            /* клиент */
            'client',                       // подгружаем связь

            /* контрагент-поставщик, товары, расходы */
            'providerItem.reference',
            'items.product.reference',
            'items.unitRef',
            'expenses.referenceItem.reference',
            'expenses.providerItem.reference',
        ])
        ->whereHas('documentType', fn ($q) => $q->where('code', 'sale'))
        ->orderByDesc('document_date')
        ->get();

    return response()->json($docs);
}
public function postSales(Request $request): JsonResponse
{
    Log::info($request->all());

    /*──────── 1. Валидация ────────*/
    $validated = $request->validate([
        'client_id'             => ['required','integer','exists:users,id'],
        'assigned_warehouse_id' => ['required','integer','exists:warehouses,id'],
        'docDate'               => ['nullable','date'],

        'products'                              => ['required','array','min:1'],
        'products.*.product.product_subcard_id' => ['required','integer','exists:reference_items,id'],
        'products.*.unit.RefferenceItem.0.id'   => ['required','integer','exists:reference_items,id'],

        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.price'     => ['required','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['required','numeric'],
    ]);

    $rows   = $validated['products'];
    $whId   = $validated['assigned_warehouse_id'];
    $client = $validated['client_id'];
    $date   = $validated['docDate'] ?? now();

    /*──────── 2. Транзакция ────────*/
    DB::beginTransaction();
    try {
        /* 2-A. «шапка» */
        $typeSale = DocumentType::where('code','sale')->firstOrFail();

        $doc = Document::create([
            'document_type_id'  => $typeSale->id,
            'status'            => '-',
            'client_id'         => $client,
            'document_date'     => Carbon::parse($date)->toDateString(),
            'from_warehouse_id' => $whId,
        ]);

        /* 2-B. строки + списание */
        foreach ($rows as $r) {

            $productId = data_get($r,'product.product_subcard_id');
            $unitId    = data_get($r,'unit.RefferenceItem.0.id');

            // название единицы измерения
            $unitName  = ReferenceItem::findOrFail($unitId)->name;

            // количество (пустая строка → 0)
            $qty = (float)($r['qtyTare'] ?? 0);

            /* ---- остаток на складе — сначала по НАЗВАНИЮ, потом по ID ---- */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $productId,
                        'unit_measurement'   => $unitName,   // «Ящик»
                    ])->first();

            if (!$stock) {
                // fallback — если старые записи хранили ID
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $productId,
                            'unit_measurement'   => $unitId,   // 10
                        ])->first();
            }

            if (!$stock || $stock->quantity < $qty) {
                throw new \Exception(
                    "Недостаточно товара {$productId} (ед. {$unitName}) на складе #{$whId}"
                );
            }

            /* себестоимость строки */
            $costTotal = ($stock->cost_price ?? 0) * $qty;

            /* ---- строка документа ---- */
            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitName,   // сохраняем НАЗВАНИЕ
                'quantity'           => $qty,
                'brutto'             => $r['brutto'] ?? 0,
                'netto'              => $r['netto']  ?? 0,
                'price'              => $r['price'],
                'total_sum'          => $r['total_sum'],
                'cost_price'         => $costTotal,
            ]);

            /* ---- списываем со склада ---- */
            $stock->quantity  -= $qty;
            $stock->brutto    -= $r['brutto'] ?? 0;
            $stock->netto     -= $r['netto']  ?? 0;
            $stock->total_sum -= $r['total_sum'];
            $stock->save();
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Продажа сохранена',
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


public function updateSales(Request $request, Document $document)
{
    /* 0. Убеждаемся, что это «Продажа» */
    $document->load('documentType');
    if ($document->documentType->code !== 'sale') {
        return response()->json(
            ['success' => false, 'error' => 'Not a sale document'], 400
        );
    }

    /* 1. Валидация ─ допускаем оба формата (новый и старый) */
    $validated = $request->validate([
        'client_id'             => ['required','integer','exists:users,id'],
        'assigned_warehouse_id' => ['required','integer','exists:warehouses,id'],
        'docDate'               => ['nullable','date'],

        'products'                              => ['required','array','min:1'],

        // новый формат
        'products.*.product.product_subcard_id' => ['required_without:products.*.product_subcard_id','integer','exists:reference_items,id'],
        'products.*.unit.RefferenceItem.0.id'   => ['required_without:products.*.unit_measurement','integer','exists:reference_items,id'],

        // старый формат
        'products.*.product_subcard_id' => ['required_without:products.*.product','integer','exists:reference_items,id'],
        'products.*.unit_measurement'   => ['required_without:products.*.unit','string'],

        // qty может быть qtyTare или quantity
        'products.*.qtyTare'     => ['nullable','numeric'],
        'products.*.quantity'    => ['nullable','numeric'],

        'products.*.price'       => ['required','numeric'],
        'products.*.brutto'      => ['nullable','numeric'],
        'products.*.netto'       => ['nullable','numeric'],
        'products.*.total_sum'   => ['required','numeric'],
    ]);

    $rows   = $validated['products'];
    $whId   = $validated['assigned_warehouse_id'];
    $client = $validated['client_id'];
    $date   = $validated['docDate'] ?? now();

    /* 2. Транзакция */
    DB::beginTransaction();
    try {
        /* 2-A. Возвращаем на склад старые списания */
        foreach ($document->items as $old) {
            $stock = WarehouseItem::firstOrNew([
                'warehouse_id'       => $document->from_warehouse_id,
                'product_subcard_id' => $old->product_subcard_id,
                'unit_measurement'   => $old->unit_measurement,   // строка-имя
            ]);

            $stock->quantity    += $old->quantity;
            $stock->brutto      += $old->brutto;
            $stock->netto       += $old->netto;
            $stock->total_sum   += $old->total_sum;
            $stock->save();
        }

        /* 2-B. Чистим старые строки */
        $document->items()->delete();

        /* 2-C. Обновляем «шапку» */
        $document->update([
            'client_id'         => $client,
            'from_warehouse_id' => $whId,
            'document_date'     => Carbon::parse($date)->toDateString(),
            'comments'          => $request->comments,
        ]);

        /* 2-D. Новые строки + реальное списание */
        foreach ($rows as $r) {

            /* ★ 1. Определяем product и НАЗВАНИЕ unit */
            $productId = data_get($r, 'product.product_subcard_id')  // новый формат
                      ?? $r['product_subcard_id'];                   // старый

            // если пришёл id справочника - конвертируем в name
            if (isset($r['unit']['RefferenceItem'][0]['id'])) {
                $unitName = $r['unit']['RefferenceItem'][0]['name'];          // новый формат
            } else {
                $unitName = $r['unit_measurement'] ?? '';                     // старый
            }

            /* ★ 2. Кол-во может быть qtyTare ИЛИ quantity */
            $qtyRaw = $r['qtyTare'] ?? $r['quantity'] ?? 0;
            $qty    = (float)($qtyRaw === '' ? 0 : $qtyRaw);

            /* ищем остаток по НАЗВАНИЮ единицы */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $productId,
                        'unit_measurement'   => $unitName,
                    ])->first();

            if (!$stock || $stock->quantity < $qty) {
                throw new \Exception(
                    "Недостаточно товара {$productId} ({$unitName}) на складе #{$whId}"
                );
            }

            $costTotal = ($stock->cost_price ?? 0) * $qty;

            /* строка документа (храним НАЗВАНИЕ) */
            DocumentItem::create([
                'document_id'        => $document->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitName,    // ★ строка-имя
                'quantity'           => $qty,
                'brutto'             => $r['brutto'] ?? 0,
                'netto'              => $r['netto']  ?? 0,
                'price'              => $r['price'],
                'total_sum'          => $r['total_sum'],
                'cost_price'         => $costTotal,
            ]);

            /* списываем со склада */
            $stock->quantity  -= $qty;
            $stock->brutto    -= $r['brutto'] ?? 0;
            $stock->netto     -= $r['netto']  ?? 0;
            $stock->total_sum -= $r['total_sum'];
            $stock->save();
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Документ (Продажа) обновлён',
            'doc_id'  => $document->id,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateSales error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}

public function indexWriteOff()
{
    /* --- 1. Загружаем документы «Списание» со всеми нужными отношениями --- */
    $docs = Document::with([
                'fromWarehouse:id,name',

                /* товары */
                'items.product.reference',
                'items.unitRef',

                /* расходы */
                'expenses.referenceItem.reference',
                'expenses.providerItem.reference',
            ])
            ->whereHas('documentType', fn ($q) => $q->where('code', 'write_off'))
            ->orderByDesc('document_date')
            ->get();

    /* --- 2. Сразу берём ВСЕ остатки для задействованных складов --- */
    $warehouseIds = $docs->pluck('from_warehouse_id')
                         ->filter()      // убираем null
                         ->unique()
                         ->values();

    if ($warehouseIds->isNotEmpty()) {

        $stocks = WarehouseItem::whereIn('warehouse_id', $warehouseIds)
                  ->get()
                  ->keyBy(fn ($row) =>
                      // уникальный ключ «склад|товар|ед.изм.»
                      $row->warehouse_id.'|'.$row->product_subcard_id.'|'.$row->unit_measurement
                  );

        /* --- 3. Вшиваем balance в каждую строку товара --- */
        foreach ($docs as $doc) {
            foreach ($doc->items as $it) {

                $key = $doc->from_warehouse_id.'|'.$it->product_subcard_id.'|'.$it->unit_measurement;
                $it->balance = $stocks[$key]->quantity ?? 0;   // 0, если остатка нет
            }
        }
    }

    /* --- 4. Отдаём результат --- */
    return response()->json($docs);
}
/**
 * Обновление уже существующего документа «Списание».
 *  – {document} подставляется в роуте  Route::put('/write-off-products/{document}', …)
 */
public function updateWriteOff(Request $request, Document $document): JsonResponse
{
    Log::info($request->all());

    /* 0. Проверяем тип документа ---------------------------------------- */
    $document->load('documentType');
    if ($document->documentType->code !== 'write_off') {
        return response()->json(
            ['success' => false, 'error' => 'Not a write-off document'], 400
        );
    }

    /* 1. ВАЛИДАЦИЯ ------------------------------------------------------- */
    $validated = $request->validate([
        'docDate'               => ['required','date'],      // 2025-04-24
        'assigned_warehouse_id' => ['required','integer','exists:warehouses,id'],

        'products'                                    => ['required','array','min:1'],
        'products.*.product.product_subcard_id'       => ['required','integer','exists:reference_items,id'],
        'products.*.unit.id'                          => ['required','integer','exists:reference_items,id'],

        'products.*.qty'          => ['nullable','numeric'], // здесь qty  **или** qtyTare – см. ниже
        'products.*.qtyTare'      => ['nullable','numeric'],

        'products.*.brutto'       => ['nullable','numeric'],
        'products.*.netto'        => ['nullable','numeric'],
        'products.*.price'        => ['nullable','numeric'],
        'products.*.total_sum'    => ['nullable','numeric'],
    ]);

    $rows    = collect($validated['products']);
    $whId    = (int) $validated['assigned_warehouse_id'];
    $docDate = Carbon::parse($validated['docDate'])->toDateString();

    /* 2. ТРАНЗАКЦИЯ ------------------------------------------------------ */
    DB::beginTransaction();
    try {
        /* 2-A. Вернём на склад всё, что было списано ранее ---------------- */
        foreach ($document->items as $old) {
            $stock = WarehouseItem::firstOrNew([
                'warehouse_id'       => $document->from_warehouse_id,
                'product_subcard_id' => $old->product_subcard_id,
                'unit_measurement'   => $old->unit_measurement,   // ← здесь всегда NAME
            ]);

            $stock->quantity            += $old->quantity;
            $stock->brutto              += $old->brutto;
            $stock->netto               += $old->netto;
            $stock->total_sum           += $old->total_sum;
            $stock->additional_expenses += $old->additional_expenses;
            $stock->save();
        }

        /* 2-B. Очищаем строки, обновляем «шапку» -------------------------- */
        $document->items()->delete();

        $document->update([
            'from_warehouse_id' => $whId,
            'document_date'     => $docDate,
            'comments'          => $request->input('comments',''),
        ]);

        /* 2-C. Формируем новые строки и сразу же списываем со склада ------ */
        $batch = [];

        foreach ($rows as $row) {
            $productId = (int) data_get($row, 'product.product_subcard_id');
            $unitId    = (int) data_get($row, 'unit.id');
            $unitName  = ReferenceItem::findOrFail($unitId)->name;   // храним имя!

            // количество может прийти в двух ключах
            $qtyRaw = $row['qty'] ?? $row['qtyTare'] ?? 0;
            $qty    = (float) ($qtyRaw === '' ? 0 : $qtyRaw);

            /* складной остаток */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $productId,
                        'unit_measurement'   => $unitName,
                    ])->first();

            if (!$stock || $stock->quantity < $qty) {
                throw new \Exception(
                    "Недостаточно товара id={$productId} ({$unitName}) на складе #{$whId}"
                );
            }

            /* коэффициенты списания */
            $ratio = $qty / $stock->quantity;

            $outBrutto = round($stock->brutto  * $ratio, 2);
            $outNetto  = round($stock->netto   * $ratio, 2);
            $outSum    = round($stock->total_sum          * $ratio, 2);
            $outAddExp = round($stock->additional_expenses* $ratio, 2);

            /* уменьшаем склад */
            $stock->quantity            -= $qty;
            $stock->brutto              -= $outBrutto;
            $stock->netto               -= $outNetto;
            $stock->total_sum           -= $outSum;
            $stock->additional_expenses -= $outAddExp;
            $stock->save();

            /* строка документа */
            $batch[] = [
                'document_id'        => $document->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitName,        // сохраняем название
                'quantity'           => $qty,
                'brutto'             => $outBrutto,
                'netto'              => $outNetto,
                'price'              => $row['price']      ?? $stock->price ?? 0,
                'total_sum'          => $outSum,
                'cost_price'         => $stock->cost_price ?? 0,
                'additional_expenses'=> $outAddExp,
                'net_unit_weight'    => $qty>0 ? round($outNetto / $qty, 4) : 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        DocumentItem::insert($batch);

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Документ «Списание» обновлён',
            'doc_id'  => $document->id,
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateWriteOff error: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}

/*==================================================================
 | ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
 *=================================================================*/

protected function rollbackToStock(Document $doc, int $whId): void
{
    foreach ($doc->items as $old) {
        $stock = WarehouseItem::firstOrCreate([
            'warehouse_id'       => $whId,
            'product_subcard_id' => $old->product_subcard_id,
            'unit_measurement'   => $old->unit_measurement,
        ]);

        $stock->quantity            += $old->quantity;
        $stock->brutto              += $old->brutto;
        $stock->netto               += $old->netto;
        $stock->total_sum           += $old->total_sum;
        $stock->additional_expenses += $old->additional_expenses;
        $stock->save();
    }
}

protected function decreaseStock(WarehouseItem $stock, float $qty): void
{
    $stock->quantity -= $qty;

    if ($stock->quantity > 0) {
        $ratio                    = $stock->quantity / ($stock->quantity + $qty);
        $stock->brutto            = round($stock->brutto * $ratio, 2);
        $stock->netto             = round($stock->netto  * $ratio, 2);
        $stock->total_sum         = round($stock->total_sum * $ratio, 2);
        $stock->additional_expenses
                                 = round($stock->additional_expenses * $ratio, 2);
    } else {
        $stock->brutto = $stock->netto = $stock->total_sum = $stock->additional_expenses = 0;
    }

    $stock->save();
}

public function postWriteOff(Request $request): \Illuminate\Http\JsonResponse
{
    Log::info($request->all());

    /* 1. ───── ВАЛИДАЦИЯ ───── */
    $validated = $request->validate([
        'docDate'               => ['required','date'],      // 2025-04-24
        'assigned_warehouse_id' => ['required','integer','exists:warehouses,id'],

        'products'                                    => ['required','array','min:1'],
        'products.*.product.product_subcard_id'       => ['required','integer','exists:reference_items,id'],
        'products.*.unit.id'                          => ['required','integer','exists:reference_items,id'],

        'products.*.qty'          => ['nullable','numeric'], // пришло "qty", а не "qtyTare"
        'products.*.brutto'       => ['nullable','numeric'],
        'products.*.netto'        => ['nullable','numeric'],
        'products.*.price'        => ['nullable','numeric'],
        'products.*.total_sum'    => ['nullable','numeric'],
    ]);

    $rows      = $validated['products'];
    $whId      = $validated['assigned_warehouse_id'];
    $docDate   = Carbon::parse($validated['docDate'])->toDateString();

    /* 2. ───── ТРАНЗАКЦИЯ ───── */
    DB::beginTransaction();
    try {
        /* 2-A. «Шапка» документа */
        $typeWriteOff = DocumentType::where('code','write_off')->firstOrFail();

        $doc = Document::create([
            'document_type_id'  => $typeWriteOff->id,
            'status'            => '-',            // расход
            'from_warehouse_id' => $whId,
            'document_date'     => $docDate,
            'comments'          => $request->input('comments',''),
        ]);

        $lines = [];   // сюда собираем строки для bulk-insert

        /* 2-B.  Каждая позиция */
        foreach ($rows as $row) {

            $productId = data_get($row,'product.product_subcard_id');
            $unitId    = data_get($row,'unit.id');
            $qty       = (float)($row['qty'] ?? 0);          // если "", то 0

            // единицу измерения храним именем, а не id  (как и раньше)
            $unitName  = ReferenceItem::findOrFail($unitId)->name;

            /* ---- остаток на складе ---- */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $productId,
                        'unit_measurement'   => $unitName,
                     ])->first();

            if (!$stock) {
                throw new \Exception("Товар id=$productId отсутствует на складе.");
            }
            if ($stock->quantity < $qty) {
                throw new \Exception("Недостаточно остатка id=$productId: нужно $qty, есть $stock->quantity");
            }

            /* ---- коэффициенты списания ---- */
            $ratio  = $qty / $stock->quantity;

            $woBrutto = round($stock->brutto  * $ratio, 2);
            $woNetto  = round($stock->netto   * $ratio, 2);
            $woSum    = round($stock->total_sum          * $ratio, 2);
            $woAddExp = round($stock->additional_expenses* $ratio, 2);

            /* ---- уменьшаем склад ---- */
            $stock->quantity            -= $qty;
            $stock->brutto              -= $woBrutto;
            $stock->netto               -= $woNetto;
            $stock->total_sum           -= $woSum;
            $stock->additional_expenses -= $woAddExp;
            $stock->save();

            /* ---- строка документа ---- */
            $lines[] = [
                'document_id'        => $doc->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitName,
                'quantity'           => $qty,
                'brutto'             => $woBrutto,
                'netto'              => $woNetto,
                'price'              => $row['price']      ?? $stock->price ?? 0,
                'total_sum'          => $woSum,
                'cost_price'         => $stock->cost_price ?? 0,
                'additional_expenses'=> $woAddExp,
                'net_unit_weight'    => $qty>0 ? round($woNetto/$qty,4) : 0,
            ];
        }

        DocumentItem::insert($lines);

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Документ «Списание» сохранён',
            'doc_id'  => $doc->id,
        ],201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('postWriteOff error: '.$e->getMessage());

        return response()->json([
            'success'=>false,
            'error'  => $e->getMessage(),
        ],500);
    }
}

public function deleteWriteOff(Document $document)
{
    /* 0.  Убеждаемся, что это именно «Списание»           */
    $document->load('documentType', 'items');
    if ($document->documentType->code !== 'write_off') {
        return response()->json(
            ['success'=>false,'error'=>'Not a write-off document'], 400
        );
    }

    $whId = $document->from_warehouse_id;    // тот же склад, что в «шапке»

    DB::beginTransaction();
    try {
        /* 1.  Возвращаем все списанные остатки на склад   */
        foreach ($document->items as $row) {

            // unit_measurement в строке хранится ИМЕНЕМ, не id
            $stock = WarehouseItem::firstOrCreate([
                'warehouse_id'       => $whId,
                'product_subcard_id' => $row->product_subcard_id,
                'unit_measurement'   => $row->unit_measurement,
            ]);

            $stock->quantity            += $row->quantity;
            $stock->brutto              += $row->brutto;
            $stock->netto               += $row->netto;
            $stock->total_sum           += $row->total_sum;
            $stock->additional_expenses += $row->additional_expenses;
            $stock->save();
        }

        /* 2.  Удаляем строки, pivot-расходы, сам документ */
        $document->items()->delete();
        $document->expenses()->delete();   // на случай, если когда-то появятся
        $document->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Документ «Списание» удалён вместе с позициями',
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('deleteWriteOff error: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Инициализация для перемещения:
     * - Список пользователей-«кладовщиков» (adminOrStorager),
     * - Остатки, если передан ?source_user_id
     */
    public function initTransfer(Request $request)
    {
        // 1) userId, по которому хотим показать товары
        $sourceUserId = $request->query('source_user_id', null);

        // 2) Список пользователей (admin или storager)
        $storagers = DB::table('users')
            ->join('role_user','users.id','=','role_user.user_id')
            ->whereIn('role_id',[1,5]) // 1=admin, 5=storager
            ->select('users.id','users.first_name','users.last_name')
            ->distinct()
            ->get();

        // Массив, который вернём на фронт
        $leftovers = [];

        // 3) Если «От кого» ещё не выбрано (нет userId), просто отдаём список storagers
        if (!$sourceUserId) {
            return response()->json([
                'storagers' => $storagers,
                'leftovers' => $leftovers,
            ]);
        }

        // 4) Выбираем все документы, где данный пользователь является `destination_user_id`
        //    Т.е. все приходы к нему
        $documentIds = Document::where('destination_user_id', $sourceUserId)->pluck('id');

        // 5) Из таблицы document_items берём все строки, которые относятся к этим документам
        $docItems = DocumentItem::whereIn('document_id', $documentIds)->get();

        // 6) Группируем по product_subcard_id, чтобы суммировать quantity
        $grouped = $docItems->groupBy('product_subcard_id');

        foreach ($grouped as $product_subcard_id => $items) {

            // Суммируем quantity по всем строкам
            $sumQty = $items->sum('quantity');

            // Можно взять единицу измерения из первой строки (если в документе оно хранится),
            // либо из product_sub_cards, если там хранится «основная» единица
            $anyRow = $items->first();
            $unitMeasurement = $anyRow->unit_measurement;

            // 7) Чтобы получить название, подгружаем данные из product_sub_cards
            $subcard = DB::table('product_sub_cards')
                ->where('id', $product_subcard_id)
                ->select('id','name')
                ->first();

            $name = $subcard ? $subcard->name : ('Unknown #'.$product_subcard_id);

            // 8) Формируем один объект «остатка»
            $leftovers[] = [
                'product_subcard_id' => $product_subcard_id,
                'name' => $name,
                'balance' => $sumQty,           // здесь — просто сумма всех «приходов» (без вычитания)
                'unit_measurement' => $unitMeasurement,
            ];
        }

        // 9) Возвращаем JSON
        return response()->json([
            'storagers' => $storagers,
            'leftovers' => $leftovers,
        ]);
    }

    public function destroyIncomes(Document $document)
    {
        /* --- 1.  Проверяем, что это именно «Приход» ---------------*/
        if (!$document->documentType || $document->documentType->code !== 'income') {
            return response()->json([
                'success' => false,
                'error'   => 'Документ не является приходом'
            ], 400);
        }

        DB::beginTransaction();
        try {
            /* --- 2.  Вернём товар со склада (=уменьшим остатки) ----*/
            $this->revertWarehouseBalances($document);

            /* --- 3.  Удаляем строки-pivot -------------------------*/
            $document->items()->delete();       // DocumentItem-ы
            $document->expenses()->delete();    // Expense-ы

            /* --- 4.  Удаляем сам документ ------------------------*/
            $document->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Документ удалён'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            // Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateSale(Request $request, int $docId): \Illuminate\Http\JsonResponse
{
    /* 1. validation (тот же набор правил, что в postSales) */
    $validated = $request->validate([
        'client_id'             => ['required','integer','exists:users,id'],
        'assigned_warehouse_id' => ['required','integer','exists:warehouses,id'],
        'docDate'               => ['nullable','date'],

        'products'                              => ['required','array','min:1'],
        'products.*.product.product_subcard_id' => ['required','integer','exists:reference_items,id'],
        'products.*.unit.RefferenceItem.0.id'   => ['required','integer','exists:reference_items,id'],

        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.price'     => ['required','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['required','numeric'],
    ]);

    $rows = $validated['products'];
    $whId = $validated['assigned_warehouse_id'];

    DB::beginTransaction();
    try {
        /* ── найти документ-sale ── */
        $doc = Document::where('id',$docId)
                ->whereHas('documentType',fn($q)=>$q->where('code','sale'))
                ->firstOrFail();

        /* ── вернуть старые остатки ── */
        foreach ($doc->items as $old) {
            $wh = WarehouseItem::where([
                     'warehouse_id'       => $doc->from_warehouse_id,
                     'product_subcard_id' => $old->product_subcard_id,
                     'unit_measurement'   => $old->unit_measurement,
                  ])->first();

            if ($wh) {
                $wh->quantity  += $old->quantity;
                $wh->brutto    += $old->brutto;
                $wh->netto     += $old->netto;
                $wh->total_sum += $old->total_sum;
                $wh->save();
            }
        }

        /* ── удалить старые строки ── */
        $doc->items()->delete();

        /* ── обновить шапку ── */
        $doc->update([
            'client_id'         => $validated['client_id'],
            'from_warehouse_id' => $whId,
            'document_date'     => \Carbon\Carbon::parse($validated['docDate'] ?? now())->toDateString(),
            'comments'          => $request->input('comments',''),
        ]);

        /* ── провести новые строки ── */
        foreach ($rows as $r) {

            $productId = data_get($r,'product.product_subcard_id');
            $unitId    = data_get($r,'unit.RefferenceItem.0.id')
                       ?? data_get($r,'product.unit_measurement');
            $qty       = (float)($r['qtyTare'] ?? 0);

            $wh = WarehouseItem::where([
                      'warehouse_id'       => $whId,
                      'product_subcard_id' => $productId,
                      'unit_measurement'   => $unitId,
                 ])->firstOrFail();

            if ($wh->quantity < $qty) {
                throw new \Exception("Недостаточно остатка товара {$productId} (ед. {$unitId})");
            }

            $costTotal = ($wh->cost_price ?? 0) * $qty;

            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitId,
                'quantity'           => $qty,
                'brutto'             => $r['brutto'] ?? 0,
                'netto'              => $r['netto']  ?? 0,
                'price'              => $r['price'],
                'total_sum'          => $r['total_sum'],
                'cost_price'         => $costTotal,
            ]);

            /* списываем со склада */
            $wh->quantity  -= $qty;
            $wh->brutto    -= $r['brutto'] ?? 0;
            $wh->netto     -= $r['netto']  ?? 0;
            $wh->total_sum -= $r['total_sum'];
            $wh->save();
        }

        DB::commit();
        return response()->json([
            'success'=>true,
            'message'=>"Sale #{$docId} updated successfully!",
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateSale error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}

    public function destroySales(Document $document)
    {
        /* --- 1.  Проверяем, что это именно «Приход» ---------------*/
        if (!$document->documentType || $document->documentType->code !== 'sale') {
            return response()->json([
                'success' => false,
                'error'   => 'Документ не является продажами'
            ], 400);
        }

        DB::beginTransaction();
        try {
            /* --- 2.  Вернём товар со склада (=уменьшим остатки) ----*/
            $this->revertWarehouseBalances($document);

            /* --- 3.  Удаляем строки-pivot -------------------------*/
            $document->items()->delete();       // DocumentItem-ы
            $document->expenses()->delete();    // Expense-ы

            /* --- 4.  Удаляем сам документ ------------------------*/
            $document->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Документ удалён'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            // Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /* -----------------------------------------------------------
     |  Вспомогательная функция:
     |  «откатить» (уменьшить) остатки по складу, если они есть
     * ---------------------------------------------------------*/
    private function revertWarehouseBalances(Document $doc): void
    {
        // нет привязки к складу →  ничего не делаем
        if (!$doc->to_warehouse_id) {
            return;
        }

        foreach ($doc->items as $item) {

            $wh = WarehouseItem::where([
                    'warehouse_id'       => $doc->to_warehouse_id,
                    'product_subcard_id' => $item->product_subcard_id,
                    'unit_measurement'   => $item->unit_measurement,
                 ])->first();

            if ($wh) {
                $wh->quantity   -= $item->quantity;
                $wh->brutto     -= $item->brutto;
                $wh->netto      -= $item->netto;
                $wh->total_sum  -= $item->total_sum;
                $wh->save();
            }
        }
    }

    /**
     * Сохранить документ типа "Перемещение"
     */
    /**
 * Создать документ «Перемещение»
 *
 * Ожидаемый JSON-формат полностью повторяет ваш скриншот:
 * {
 *   "from_warehouse_id": 1,
 *   "to_warehouse_id"  : 2,
 *   "docDate"          : "2025-04-26",
 *   "products":[
 *     {
 *       "product":{"product_subcard_id":4},
 *       "unit"   :{"id":10},
 *       "qty"    :"2",              ← количество
 *       "price"  :"1000",
 *       "brutto" :"80",
 *       "netto"  :"75",
 *       "total_sum":"2000"
 *     }
 *   ]
 * }
 */
/*--------------------------------------------------------
 |  Перемещение товара: со склада-источника → склад-получатель
 |  POST  /api/transfer-products
 *-------------------------------------------------------*/
public function storeTransfer(Request $request): JsonResponse
{
    Log::info($request->all());

    /*──── 1. ВАЛИДАЦИЯ ────*/
    $data = $request->validate([
        'from_warehouse_id'                     => ['required','integer','exists:warehouses,id'],
        'to_warehouse_id'                       => ['required','integer','different:from_warehouse_id','exists:warehouses,id'],
        'docDate'                               => ['nullable','date'],

        'products'                              => ['required','array','min:1'],
        'products.*.product.product_subcard_id' => ['required','integer','exists:reference_items,id'],
        'products.*.unit.id'                    => ['required','integer','exists:reference_items,id'],

        'products.*.qtyTare'   => ['nullable','numeric'],
        'products.*.qty'       => ['nullable','numeric'],

        'products.*.price'     => ['nullable','numeric'],
        'products.*.brutto'    => ['nullable','numeric'],
        'products.*.netto'     => ['nullable','numeric'],
        'products.*.total_sum' => ['nullable','numeric'],
    ]);

    $rows     = $data['products'];
    $fromWhId = $data['from_warehouse_id'];
    $toWhId   = $data['to_warehouse_id'];
    $docDate  = $data['docDate'] ?? now();

    /*──── 2. ТРАНЗАКЦИЯ ────*/
    DB::beginTransaction();
    try {
        /* 2-A. «Шапка» */
        $typeTransfer = DocumentType::where('code', 'transfer')->firstOrFail();
        $doc = Document::create([
            'document_type_id'  => $typeTransfer->id,
            'status'            => '-',    // минус со склада-источника
            'document_date'     => Carbon::parse($docDate)->toDateString(),
            'from_warehouse_id' => $fromWhId,
            'to_warehouse_id'   => $toWhId,
            'comments'          => $request->input('comments', ''),
        ]);

        /* 2-B. Перебираем товары */
        foreach ($rows as $row) {

            /* --- исходные данные --- */
            $productId = data_get($row,'product.product_subcard_id');
            $unitId    = data_get($row,'unit.id');

            // количество: либо qtyTare, либо qty
            $qtyRaw = $row['qtyTare'] ?? $row['qty'] ?? 0;
            $qty    = (float)($qtyRaw === '' ? 0 : $qtyRaw);

            /* --- название единицы измерения --- */
            $unitName = ReferenceItem::findOrFail($unitId)->name;

            /*──────── ❶ Остаток на складе-ИСТОЧНИКЕ ────────*/
            $src = WarehouseItem::where([
                      'warehouse_id'       => $fromWhId,
                      'product_subcard_id' => $productId,
                      'unit_measurement'   => $unitName,   // ищем по НАЗВАНИЮ
                  ])->first();

            if (!$src || $src->quantity < $qty) {
                throw new \Exception(
                    "Недостаточно товара id={$productId} ({$unitName}) на складе #{$fromWhId}"
                );
            }

            /* пропорциональные величины */
            $ratio   = $src->quantity > 0 ? $qty / $src->quantity : 0;
            $trBrut  = round($src->brutto  * $ratio, 2);
            $trNet   = round($src->netto   * $ratio, 2);
            $trSum   = round($src->total_sum          * $ratio, 2);
            $trAddEx = round($src->additional_expenses* $ratio, 2);

            /*──────── ❷ Списываем со склада-ИСТОЧНИКА ────────*/
            $src->quantity -= $qty;
            if ($src->quantity > 0) {
                $k = $src->quantity / ($src->quantity + $qty);
                $src->brutto              = round($src->brutto * $k, 2);
                $src->netto               = round($src->netto  * $k, 2);
                $src->total_sum           = round($src->total_sum * $k, 2);
                $src->additional_expenses = round($src->additional_expenses * $k, 2);
            } else {
                $src->brutto = $src->netto = $src->total_sum = $src->additional_expenses = 0;
            }
            $src->save();

            /*──────── ❸ Приходуем на склад-ПОЛУЧАТЕЛЬ ────────*/
            $dst = WarehouseItem::firstOrNew([
                      'warehouse_id'       => $toWhId,
                      'product_subcard_id' => $productId,
                      'unit_measurement'   => $unitName,   // ⚠︎ ищем и храним по НАЗВАНИЮ
                  ]);

            $dst->quantity            += $qty;
            $dst->brutto              += $trBrut;
            $dst->netto               += $trNet;
            $dst->total_sum           += $trSum;
            $dst->additional_expenses += $trAddEx;
            $dst->price                = $row['price']    ?? ($src->price      ?? 0);
            $dst->cost_price           = $src->cost_price ?? 0;
            $dst->save();

            /*──────── ❹ Строка документа ────────*/
            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitName,      // ← сохраняем название
                'quantity'           => $qty,
                'brutto'             => $trBrut,
                'netto'              => $trNet,
                'price'              => $row['price'] ?? ($src->price ?? 0),
                'total_sum'          => $trSum,
                'additional_expenses'=> $trAddEx,
                'cost_price'         => $src->cost_price ?? 0,
                'net_unit_weight'    => $qty > 0 ? round($trNet / $qty, 4) : 0,
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Перемещение сохранено',
            'doc_id'  => $doc->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('storeTransfer error: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}



/**
     * Сохранить документ типа "Перемещение"
     */
    public function initWriteOff(Request $request)
{
    $sourceWarehouseId = $request->query('source_warehouse_id');

    // All warehouses
    $warehouses = Warehouse::select('id','name')->get();

    // If no warehouse selected yet, return empty leftovers
    if (!$sourceWarehouseId) {
        return response()->json([
            'warehouses' => $warehouses,
            'leftovers'  => [],
        ]);
    }

    // Otherwise, fetch from warehouse_items
    $items = WarehouseItem::where('warehouse_id', $sourceWarehouseId)->get();

    $leftovers = [];
    foreach ($items as $whItem) {
        // Optionally load product name from product_sub_cards
        $product = DB::table('product_sub_cards')
            ->where('id', $whItem->product_subcard_id)
            ->select('id','name')
            ->first();

        $leftovers[] = [
            'product_subcard_id' => $whItem->product_subcard_id,
            'name'               => $product ? $product->name : ('Unknown #'.$whItem->product_subcard_id),
            'balance'            => $whItem->quantity,
            'unit_measurement'   => $whItem->unit_measurement,
        ];
    }

    return response()->json([
        'warehouses' => $warehouses,
        'leftovers'  => $leftovers,
    ], 200);
}


    /**
     * Сохранить документ типа "Списание" (document_type_id=4)
     *
     * Пример входных данных:
     * {
     *   "user_id": 5,
     *   "document_date": "2025-03-10",
     *   "items": [
     *     { "product_subcard_id":2, "quantity":10, "unit_measurement":"шт" }
     *   ]
     * }
     */
    public function storeWriteOff(Request $request)
{
    $validated = $request->validate([
        'warehouse_id'  => 'required|integer',
        'document_date' => 'required|date',
        'items'         => 'required|array|min:1',
    ]);

    $warehouseId = $validated['warehouse_id'];
    $docDate     = $validated['document_date'];
    $items       = $validated['items'];

    DB::beginTransaction();
    try {
        // Тип документа "write_off"
        $docType = DocumentType::where('code', 'write_off')->firstOrFail();

        // "Шапка" документа
        $doc = Document::create([
            'document_type_id'  => $docType->id,
            'status'            => '-',
            'from_warehouse_id' => $warehouseId,
            'to_warehouse_id'   => 0,
            'document_date'     => $docDate,
            'comments'          => "Списание со склада #$warehouseId",
        ]);

        foreach ($items as $row) {
            $prodId   = $row['product_subcard_id'];
            $qty      = $row['quantity']         ?? 0;
            $uMeasure = $row['unit_measurement'] ?? '';

            // Находим остаток на складе
            $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                ->where('product_subcard_id', $prodId)
                ->where('unit_measurement', $uMeasure)
                ->first();

            if (!$whItem || $whItem->quantity < $qty) {
                throw new \Exception("Недостаточно товара (ID=$prodId) на складе $warehouseId для списания $qty.");
            }

            // 1) Списываем
            $whItem->quantity -= $qty;
            // Аналогично brutto/netto — если хотите пропорционально:
            $oldQty = max($whItem->quantity + $qty, 0);  // до списания
            if ($oldQty > 0) {
                $ratio = $qty / $oldQty; // доля, которую вычитаем
                $minusBrutto = round($whItem->brutto * $ratio, 2);
                $minusNetto  = round($whItem->netto  * $ratio, 2);
                $minusSum    = round($whItem->total_sum * $ratio, 2);
                $minusExp    = round($whItem->additional_expenses * $ratio, 2);

                $whItem->brutto    = round($whItem->brutto    - $minusBrutto, 2);
                $whItem->netto     = round($whItem->netto     - $minusNetto , 2);
                $whItem->total_sum = round($whItem->total_sum - $minusSum   , 2);
                $whItem->additional_expenses = round($whItem->additional_expenses - $minusExp, 2);
            }
            if ($whItem->quantity < 0) {
                throw new \Exception("Ошибка: остаток ушел в минус.");
            }
            $whItem->save();

            // 2) Теперь $whItem->brutto, $whItem->netto, $whItem->quantity и т.п. — это уже «остаток»
            //    если, например, было 80, списали 10 => осталось 70
            $netUnitWeight = ($whItem->quantity > 0)
                ? round($whItem->netto / $whItem->quantity, 4)
                : 0;

            // 3) Записываем в DocumentItem "новый остаток"
            DocumentItem::create([
                'document_id'         => $doc->id,
                'product_subcard_id'  => $prodId,
                'unit_measurement'    => $uMeasure,
                // ВАЖНО: теперь quantity/brutto/netto — это «остаток», а не «списанная часть»
                'quantity'            => $whItem->quantity,
                'brutto'              => $whItem->brutto,
                'netto'               => $whItem->netto,
                'price'               => $whItem->price,
                'total_sum'           => $whItem->total_sum,
                'additional_expenses' => $whItem->additional_expenses,
                'cost_price'          => $whItem->cost_price,
                'net_unit_weight'     => $netUnitWeight,
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => "Списание успешно (документ #$doc->id)."
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}

// перемещение
public function indexTransfers(): \Illuminate\Http\JsonResponse
{
    $docs = Document::with([
                /* --- склады-участники --- */
                'fromWarehouse:id,name',
                'toWarehouse:id,name',

                /* --- товарные строки --- */
                'items.product.reference',   // под-карточка → карточка-товара
                'items.unitRef',             // единица измерения (ReferenceItem)

                /* --- (на всякий случай) расходы, хотя для transfer они обычно пустые --- */
                'expenses.referenceItem.reference',
                'expenses.providerItem.reference',
            ])
            ->whereHas('documentType', fn ($q) => $q->where('code', 'transfer'))
            ->orderByDesc('document_date')
            ->get();

    /* отдаём «как есть»: все загруженные отношения уйдут в JSON */
    return response()->json($docs);
}
public function postTransfer(Request $request): \Illuminate\Http\JsonResponse
{
    Log::info($request->all());

    /* ---------- 1. Валидация ---------- */
    $v = $request->validate([
        'from_warehouse_id' => ['required','integer','exists:warehouses,id','different:to_warehouse_id'],
        'to_warehouse_id'   => ['required','integer','exists:warehouses,id'],
        'docDate'           => ['nullable','date'],

        'products'                             => ['required','array','min:1'],

        // вложенные объекты новой структуры
        'products.*.product.id'                => ['required','integer','exists:reference_items,id'],
        'products.*.unit.id'                   => ['required','integer','exists:reference_items,id'],

        'products.*.qtyTare'     => ['nullable','numeric'],
        'products.*.price'       => ['nullable','numeric'],
        'products.*.brutto'      => ['nullable','numeric'],
        'products.*.netto'       => ['nullable','numeric'],
        'products.*.total_sum'   => ['nullable','numeric'],
    ]);

    $rows     = $v['products'];
    $fromWh   = $v['from_warehouse_id'];
    $toWh     = $v['to_warehouse_id'];
    $docDate  = Carbon::parse($v['docDate'] ?? now())->toDateString();

    /* ---------- 2. Транзакция ---------- */
    DB::beginTransaction();
    try {
        /* 2-A. шапка */
        $typeTransfer = DocumentType::where('code','transfer')->firstOrFail();

        $doc = Document::create([
            'document_type_id'  => $typeTransfer->id,
            'status'            => '-',               // документ “расходный” из точки А
            'from_warehouse_id' => $fromWh,
            'to_warehouse_id'   => $toWh,
            'document_date'     => $docDate,
        ]);

        /* 2-B. обработка строк */
        foreach ($rows as $r) {
            $productId = data_get($r,'product.id');
            $unitId    = data_get($r,'unit.id');
            $unitName  = ReferenceItem::find($unitId)->name;      // для старых остатков
            $qty       = (float)($r['qtyTare'] ?? 0);

            /** ---- ① списываем со «склада-отправителя» ---- */
            $src = WarehouseItem::where([
                       'warehouse_id'       => $fromWh,
                       'product_subcard_id' => $productId,
                       'unit_measurement'   => $unitName,
                   ])->first();
            if(!$src || $src->quantity < $qty){
                throw new \Exception("На складе-отправителе недостаточно товара id={$productId}");
            }

            // доли для списания
            $ratio   = $src->quantity > 0 ? $qty / $src->quantity : 0;
            $srcDeduct = [
                'brutto'             => round($src->brutto  * $ratio, 2),
                'netto'              => round($src->netto   * $ratio, 2),
                'total_sum'          => round($src->total_sum * $ratio,2),
                'additional_expenses'=> round($src->additional_expenses * $ratio,2),
            ];

            $src->quantity  -= $qty;
            $src->brutto    -= $srcDeduct['brutto'];
            $src->netto     -= $srcDeduct['netto'];
            $src->total_sum -= $srcDeduct['total_sum'];
            $src->additional_expenses -= $srcDeduct['additional_expenses'];
            $src->save();

            /** ---- ② добавляем на «склад-получатель» ---- */
            $dst = WarehouseItem::firstOrNew([
                       'warehouse_id'       => $toWh,
                       'product_subcard_id' => $productId,
                       'unit_measurement'   => $unitName,
                   ]);
            $dst->quantity            += $qty;
            $dst->brutto              += $srcDeduct['brutto'];
            $dst->netto               += $srcDeduct['netto'];
            $dst->total_sum           += $srcDeduct['total_sum'];
            $dst->additional_expenses += $srcDeduct['additional_expenses'];
            // цена/себестоимость – берём из отправителя
            $dst->price      = $src->price      ?? ($r['price'] ?? 0);
            $dst->cost_price = $src->cost_price ?? ($r['cost_price'] ?? 0);
            $dst->save();

            /** ---- ③ строка документа ---- */
            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => $productId,
                'unit_measurement'   => $unitName,
                'quantity'           => $qty,
                'brutto'             => $srcDeduct['brutto'],
                'netto'              => $srcDeduct['netto'],
                'price'              => $dst->price,
                'total_sum'          => $srcDeduct['total_sum'],
                'cost_price'         => $dst->cost_price,
                'additional_expenses'=> $srcDeduct['additional_expenses'],
                'net_unit_weight'    => $qty>0 ? round($srcDeduct['netto']/$qty,4):0,
            ]);
        }

        DB::commit();
        return response()->json([
            'success'=>true,
            'message'=>'Перемещение сохранено',
            'doc_id' =>$doc->id,
        ],201);

    }catch(\Throwable $e){
        DB::rollBack();
        Log::error('storeTransfer error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}
protected function revertTransferBalances(Document $doc): void
{
    $from = $doc->from_warehouse_id;
    $to   = $doc->to_warehouse_id;

    foreach ($doc->items as $it) {
        $filter = [
            'product_subcard_id' => $it->product_subcard_id,
            'unit_measurement'   => $it->unit_measurement,   // имя «Ящик»
        ];

        // − со склада-получателя
        if ($dst = WarehouseItem::where($filter + ['warehouse_id'=>$to])->first()) {
            $dst->quantity            -= $it->quantity;
            $dst->brutto              -= $it->brutto;
            $dst->netto               -= $it->netto;
            $dst->total_sum           -= $it->total_sum;
            $dst->additional_expenses -= $it->additional_expenses;
            $dst->save();
        }

        // + на склад-источник
        $src = WarehouseItem::firstOrNew($filter + ['warehouse_id'=>$from]);
        $src->quantity            += $it->quantity;
        $src->brutto              += $it->brutto;
        $src->netto               += $it->netto;
        $src->total_sum           += $it->total_sum;
        $src->additional_expenses += $it->additional_expenses;
        $src->price                = $it->price      ?? $src->price;
        $src->cost_price           = $it->cost_price ?? $src->cost_price;
        $src->save();
    }
}

/*----------------------------------------------------------
| 2. helper – переносим одну позицию и пишем строку
*---------------------------------------------------------*/
protected function moveOneRow(
    Document $doc,
    array    $row,
    int      $from,
    int      $to
): void
{
    $productId = (int) data_get($row,'product.product_subcard_id');
    $unitId    = (int) data_get($row,'unit.id');
    $unitName  = ReferenceItem::findOrFail($unitId)->name;

    $rawQty = $row['qtyTare'] ?? $row['qty'] ?? 0;
    $qty    = (float)($rawQty === '' ? 0 : $rawQty);

    /* --- остаток на источнике --- */
    $src = WarehouseItem::where([
              'warehouse_id'       => $from,
              'product_subcard_id' => $productId,
              'unit_measurement'   => $unitName,
          ])->first();

    if (!$src || $src->quantity < $qty) {
        throw new \Exception("Недостаточно товара id={$productId} ({$unitName}) на складе #{$from}");
    }

    $k       = $qty / $src->quantity;                    // доля
    $deltaBr = round($src->brutto  * $k, 2);
    $deltaNt = round($src->netto   * $k, 2);
    $deltaSm = round($src->total_sum          * $k, 2);
    $deltaEx = round($src->additional_expenses* $k, 2);

    /* – со склада-источника */
    $src->quantity            -= $qty;
    $src->brutto              -= $deltaBr;
    $src->netto               -= $deltaNt;
    $src->total_sum           -= $deltaSm;
    $src->additional_expenses -= $deltaEx;
    $src->save();

    /* + на склад-получатель */
    $dst = WarehouseItem::firstOrNew([
              'warehouse_id'       => $to,
              'product_subcard_id' => $productId,
              'unit_measurement'   => $unitName,
          ]);

    $dst->quantity            += $qty;
    $dst->brutto              += $deltaBr;
    $dst->netto               += $deltaNt;
    $dst->total_sum           += $deltaSm;
    $dst->additional_expenses += $deltaEx;
    $dst->price       = $row['price'] ?? ($src->price ?? 0);
    $dst->cost_price  = $src->cost_price ?? 0;
    $dst->save();

    /* строка в документ */
    DocumentItem::create([
        'document_id'        => $doc->id,
        'product_subcard_id' => $productId,
        'unit_measurement'   => $unitName,                 // сохраняем НАЗВАНИЕ
        'quantity'           => $qty,
        'brutto'             => $deltaBr,
        'netto'              => $deltaNt,
        'price'              => $row['price'] ?? ($src->price ?? 0),
        'total_sum'          => $deltaSm,
        'additional_expenses'=> $deltaEx,
        'cost_price'         => $src->cost_price ?? 0,
        'net_unit_weight'    => $qty>0 ? round($deltaNt/$qty,4) : 0,
    ]);
}

/*----------------------------------------------------------
| 3. главный метод PUT  /transfer-products/{document}
*---------------------------------------------------------*/
public function updateTransfer(Request $request, Document $document): JsonResponse
{
    /* 0. проверяем тип */
    $document->load('documentType');
    if ($document->documentType->code !== 'transfer') {
        return response()->json(['success'=>false,'error'=>'Not a transfer document'],400);
    }

    /* 1. валидация */
    $data = $request->validate([
        'from_warehouse_id'                     => ['required','integer','exists:warehouses,id'],
        'to_warehouse_id'                       => ['required','integer','different:from_warehouse_id','exists:warehouses,id'],
        'docDate'                               => ['nullable','date'],
        'products'                              => ['required','array','min:1'],
        'products.*.product.product_subcard_id' => ['required','integer','exists:reference_items,id'],
        'products.*.unit.id'                    => ['required','integer','exists:reference_items,id'],
        'products.*.qtyTare'                    => ['nullable','numeric'],
        'products.*.qty'                        => ['nullable','numeric'],
        'products.*.price'                      => ['nullable','numeric'],
        'products.*.brutto'                     => ['nullable','numeric'],
        'products.*.netto'                      => ['nullable','numeric'],
        'products.*.total_sum'                  => ['nullable','numeric'],
    ]);

    $rows     = $data['products'];
    $fromId   = (int) $data['from_warehouse_id'];
    $toId     = (int) $data['to_warehouse_id'];
    $docDate  = Carbon::parse($data['docDate'] ?? now())->toDateString();

    DB::beginTransaction();
    try {
        /* 2. откат + очистка */
        $this->revertTransferBalances($document);
        $document->items()->delete();

        /* 3. апдейт шапки */
        $document->update([
            'from_warehouse_id' => $fromId,
            'to_warehouse_id'   => $toId,
            'document_date'     => $docDate,
            'comments'          => $request->input('comments',''),
        ]);

        /* 4. новые строки + движение остатков */
        foreach ($rows as $row) {
            $this->moveOneRow($document, $row, $fromId, $toId);
        }

        DB::commit();
        return response()->json([
            'success'=>true,
            'message'=>'Перемещение обновлено',
            'doc_id' =>$document->id,
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('updateTransfer error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}
#############################################
#  C.  УДАЛЕНИЕ                             #
#############################################
public function destroyTransfer(Document $document)
{
    if(!$document->documentType || $document->documentType->code!=='transfer'){
        return response()->json(['success'=>false,'error'=>'Not a transfer document'],400);
    }

    DB::beginTransaction();
    try{
        /* возвращаем остатки назад */
        foreach($document->items as $it){

            // to → from
            $src = WarehouseItem::firstOrNew([
                     'warehouse_id'       => $document->from_warehouse_id,
                     'product_subcard_id' => $it->product_subcard_id,
                     'unit_measurement'   => $it->unit_measurement
                   ]);
            $src->quantity            += $it->quantity;
            $src->brutto              += $it->brutto;
            $src->netto               += $it->netto;
            $src->total_sum           += $it->total_sum;
            $src->additional_expenses += $it->additional_expenses;
            $src->save();

            $dst = WarehouseItem::where([
                     'warehouse_id'       => $document->to_warehouse_id,
                     'product_subcard_id' => $it->product_subcard_id,
                     'unit_measurement'   => $it->unit_measurement
                   ])->first();
            if($dst){
                $dst->quantity            -= $it->quantity;
                $dst->brutto              -= $it->brutto;
                $dst->netto               -= $it->netto;
                $dst->total_sum           -= $it->total_sum;
                $dst->additional_expenses -= $it->additional_expenses;
                $dst->save();
            }
        }

        $document->items()->delete();
        $document->delete();

        DB::commit();
        return response()->json(['success'=>true,'message'=>'Перемещение удалено']);
    }catch(\Throwable $e){
        DB::rollBack();
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}

}
