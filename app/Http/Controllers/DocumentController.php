<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentItem;
use App\Models\Warehouse;
use App\Models\WarehouseItem;

class DocumentController extends Controller
{
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


    /**
     * Сохранить документ типа "Перемещение"
     */
    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'source_warehouse_id'      => 'required|integer',
            'destination_warehouse_id' => 'required|integer|different:source_warehouse_id',
            'document_date'            => 'required|date',
            'products'                 => 'required|array|min:1',
        ]);

        $sourceWhId = $validated['source_warehouse_id'];
        $destWhId   = $validated['destination_warehouse_id'];
        $docDate    = $validated['document_date'];
        $products   = $validated['products'];

        DB::beginTransaction();
        try {
            // 1) Тип документа "transfer" (где code='transfer')
            $docType = DocumentType::where('code', 'transfer')->firstOrFail();

            // 2) «Шапка» документа
            $doc = Document::create([
                'document_type_id'  => $docType->id,
                // обычно 'status' = '-' (товар ушёл со склада-источника)
                'status'            => '-',
                'from_warehouse_id' => $sourceWhId,
                'to_warehouse_id'   => $destWhId,
                'document_date'     => $docDate,
                'comments'          => "Перемещение со склада #$sourceWhId в #$destWhId",
            ]);

            // 3) Для каждого товара
            foreach ($products as $p) {
                $prodId = $p['product_subcard_id'];
                $qty    = $p['quantity']        ?? 0;
                $um     = $p['unit_measurement'] ?? '';

                // A) Уменьшаем на складе-источнике
                $whItem = WarehouseItem::where('warehouse_id', $sourceWhId)
                    ->where('product_subcard_id', $prodId)
                    ->where('unit_measurement', $um)
                    ->first();

                if (!$whItem) {
                    throw new \Exception("Товар (ID=$prodId, ед=$um) не найден на складе-источнике $sourceWhId.");
                }
                if ($whItem->quantity < $qty) {
                    throw new \Exception("Недостаточно товара (ID=$prodId) на складе $sourceWhId для перемещения $qty.");
                }

                // Запоминаем «старое» кол-во (до перемещения)
                $oldQty    = $whItem->quantity;
                $oldBrutto = $whItem->brutto;
                $oldNetto  = $whItem->netto;
                // Остальное тоже можно использовать

                // Списываем qty
                $whItem->quantity -= $qty;

                // Пропорционально уменьшаем brutto/netto/...
                if ($oldQty > 0) {
                    $ratio = $qty / $oldQty;
                    $minusBrutto = round($oldBrutto * $ratio, 2);
                    $minusNetto  = round($oldNetto  * $ratio, 2);
                    // Аналогично total_sum, additional_expenses, etc.

                    $whItem->brutto = round($whItem->brutto - $minusBrutto, 2);
                    $whItem->netto  = round($whItem->netto  - $minusNetto , 2);
                    // и т.д.
                }

                if ($whItem->quantity < 0) {
                    throw new \Exception("Ошибка: Остаток на складе-источнике ушёл в минус.");
                }
                $whItem->save();

                // B) Добавляем на склад-получатель
                $destItem = WarehouseItem::where('warehouse_id', $destWhId)
                    ->where('product_subcard_id', $prodId)
                    ->where('unit_measurement', $um)
                    ->first();

                if (!$destItem) {
                    $destItem = new WarehouseItem();
                    $destItem->warehouse_id       = $destWhId;
                    $destItem->product_subcard_id = $prodId;
                    $destItem->unit_measurement   = $um;
                    $destItem->quantity           = 0;
                    $destItem->brutto             = 0;
                    $destItem->netto              = 0;
                    // etc.
                }

                // Прибавляем те же qty, brutto, netto (пропорционально)
                // (или используем другую логику — как вам надо)
                $destItem->quantity += $qty;
                // destItem->brutto   += $minusBrutto;
                // destItem->netto    += $minusNetto;
                // и т.д.

                $destItem->save();

                // C) Теперь в $whItem->(quantity/brutto/netto) — остаток после отгрузки
                // Можно записать именно этот «остаток» в document_items
                $netUnitWeight = ($whItem->quantity > 0)
                    ? round($whItem->netto / $whItem->quantity, 4)
                    : 0;

                DocumentItem::create([
                    'document_id'       => $doc->id,
                    'product_subcard_id'=> $prodId,
                    'unit_measurement'  => $um,
                    // ВАЖНО: записываем «остаток» на source-складе после отгрузки
                    'quantity' => $whItem->quantity,
                    'brutto'   => $whItem->brutto,
                    'netto'    => $whItem->netto,
                    // price, total_sum, cost_price, etc.
                    'net_unit_weight' => $netUnitWeight,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Перемещение успешно (ID документа $doc->id).",
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
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



}
