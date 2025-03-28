<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdminWarehouse;   // HEAD line kept
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Expense;
use Illuminate\Http\Request;
use App\Models\GeneralWarehouse;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Unit_measurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use DocumentsRequests;
use GeneralWarehouses;           // HEAD line kept
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // HEAD line kept
use Illuminate\Support\Facades\Log;

class StorageController extends Controller
{

    public function getStorageSales()
{
    // e.g. find doc_type 'sale', match user->warehouse if needed
    $warehouseId = Warehouse::where('manager_id', Auth::id())->first()->id;

    $documents = Document::whereHas('documentType', function($q) {
            $q->where('code', 'sale');
        })
        ->where('from_warehouse_id', $warehouseId) // or whichever logic
        ->with(['documentItems', 'client'])
        ->get();

    return response()->json($documents, 200);
}
    /**
     * Get all storage users with "storage" role.
     */
    public function getStorageUsers()
    {
        try {
            $storageUsers = User::whereHas('roles', function ($query) {
                $query->where('name', 'storager');
            })->get(['id', 'first_name', 'last_name', 'surname','address']);

            return response()->json($storageUsers, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching storage users:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch storage users'], 500);
        }
    }

    public function getAllInstances(Request $request)
    {
        $clientsAndAddresses = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->with('addresses')->get(['id', 'first_name', 'last_name', 'surname']);

        $productSubCards = ProductSubCard::all();
        $unitMeasurements = Unit_measurement::all();
        $providers = Provider::all();
        $expenses = Expense::all();

        return response()->json([
            'providers' => $providers,
            'clients'           => $clientsAndAddresses,
            'product_sub_cards' => $productSubCards,
            'unit_measurements' => $unitMeasurements,
            'expenses' => $expenses,
        ]);
    }





    public function fetchSalesReport()
{
    // Получаем текущего пользователя
    $authUserId = Auth::id();

    // 1. Фильтруем DocumentRequest так, чтобы брать только те записи,
    //    которые принадлежат текущему пользователю (если в DocumentRequest есть 'auth_user_id')
    $sales = DocumentRequest::with('productSubcard', 'unitMeasurement')
        ->where('auth_user_id', $authUserId)
        ->get()
        ->map(function ($sale) use ($authUserId) {
            // 2. Находим остаток в GeneralWarehouse для этого subcard,
            //    тоже привязанный к auth_user_id, если у нас там есть это поле
            $remainingQuantity = GeneralWarehouse::where('auth_user_id', $authUserId)
                ->where('product_subcard_id', $sale->product_subcard_id)
                ->sum('quantity');

            // Формируем массив для каждой строки
            return [
                'product'   => $sale->productSubcard->name ?? 'Unknown',
                'unit'      => $sale->unitMeasurement->name ?? 'Unknown',
                'quantity'  => $sale->amount ?? 0,
                'price'     => $sale->price ?? 0,
                'total'     => ($sale->amount ?? 0) * ($sale->price ?? 0),
                'remaining' => $remainingQuantity ?? 0,
            ];
        });

    return response()->json(['sales' => $sales]);
}

public function storeIncomeAsWarehouseManager(Request $request)
{
    Log::info($request->all());

    // 1. Проверяем аутентификацию
    $user = Auth::user();
    if (!$user) {
        return response()->json([
            'success' => false,
            'error' => 'Not authenticated'
        ], 401);
    }

    // 2. Ищем склад, которым управляет данный пользователь (manager_id)
    $warehouse = Warehouse::where('manager_id', $user->id)->first();
    if (!$warehouse) {
        return response()->json([
            'success' => false,
            'error' => 'У вас нет склада, так как вы не являетесь manager_id ни одного склада'
        ], 422);
    }
    $warehouseId = $warehouse->id;

    // 3. Извлекаем массив приходов (receivings)
    $receivings = $request->input('receivings', []);
    if (empty($receivings)) {
        return response()->json([
            'success' => false,
            'error'   => 'Массив receivings пуст или не передан'
        ], 422);
    }

    DB::beginTransaction();
    try {
        // 4. Находим тип документа с code='income'
        $docType = DocumentType::where('code', 'income')->firstOrFail();

        // 5. Проходимся по каждому элементу из receivings
        foreach ($receivings as $receiving) {
            $providerId = $receiving['provider_id']    ?? null;
            $docDate    = $receiving['document_date']  ?? null;
            $products   = $receiving['products']       ?? [];
            $expenses   = $receiving['expenses']       ?? [];

            // Проверяем, есть ли товары
            if (empty($products)) {
                throw new \Exception("В одном из приходов отсутствует массив products.");
            }

            // Создаём документ
            $doc = Document::create([
                'document_type_id' => $docType->id,
                'status'           => '+',  // Приход
                'provider_id'      => $providerId,
                'document_date'    => $docDate ?: now(),
                'comments'         => $receiving['comments'] ?? null,
                // Склад берём автоматически — склад менеджера
                'to_warehouse_id'  => $warehouseId,
            ]);

            // 6. Сохраняем строки документа (DocumentItem) и обновляем склад
            foreach ($products as $item) {
                // Создаём строку документа
                DocumentItem::create([
                    'document_id'         => $doc->id,
                    'product_subcard_id'  => $item['product_subcard_id']  ?? null,
                    'unit_measurement'    => $item['unit_measurement']    ?? null,
                    'quantity'            => $item['quantity']            ?? 0,
                    'brutto'              => $item['brutto']              ?? 0,
                    'netto'               => $item['netto']               ?? 0,
                    'price'               => $item['price']               ?? 0,
                    'total_sum'           => $item['total_sum']           ?? 0,
                    'additional_expenses' => $item['additional_expenses'] ?? 0,
                    'cost_price'          => $item['cost_price']          ?? 0,
                    'net_unit_weight'     => ($item['quantity'] ?? 0) > 0
        ? ($item['netto'] / $item['quantity'])
        : 0,
                ]);

                // Обновляем (или создаём) данные на складе
                $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $item['product_subcard_id'] ?? null)
                    ->where('unit_measurement', $item['unit_measurement'] ?? null)
                    ->first();

                if (!$whItem) {
                    $whItem = new WarehouseItem();
                    $whItem->warehouse_id       = $warehouseId;
                    $whItem->product_subcard_id = $item['product_subcard_id']  ?? null;
                    $whItem->unit_measurement   = $item['unit_measurement']    ?? null;
                    $whItem->quantity           = 0;
                    $whItem->brutto             = 0;
                    $whItem->netto              = 0;
                    $whItem->total_sum          = 0;
                }

                // Добавляем количество и суммы к тому, что уже хранится на складе
                $whItem->quantity  += ($item['quantity'] ?? 0);
                $whItem->brutto   += ($item['brutto']   ?? 0);
                $whItem->netto    += ($item['netto']    ?? 0);

                // Для простоты «последняя цена» на складе
                $whItem->price                = $item['price']               ?? 0;
                $whItem->additional_expenses  = $item['additional_expenses'] ?? 0;
                $whItem->cost_price           = $item['cost_price']          ?? 0;

                // Увеличиваем общую сумму, хранящуюся на складе
                $whItem->total_sum += ($item['total_sum'] ?? 0);

                $whItem->save();
            }

            // 7. Связываем расходы (expenses) с документом
            foreach ($expenses as $exp) {
                // Находим уже существующую запись расхода
                $existingExpense = Expense::findOrFail($exp['expense_id']);
                $existingExpense->update([
                    'document_id' => $doc->id,
                    // Можно писать какое-то имя, если оно приходит с фронта
                    'name'        => $exp['name']   ?? 'Расход',
                    'amount'      => $exp['amount'] ?? 0,
                ]);
            }
        }

        // Если всё прошло без ошибок — подтверждаем транзакцию
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Все приходные документы успешно сохранены.'
        ], 201);

    } catch (\Throwable $e) {
        // При любой ошибке откатываем изменения
        DB::rollBack();

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage()
        ], 500);
    }
}



public function getAllReceipts(Request $request)
{
    // We want only documents of type 'income',
    // and also where to_warehouse_id = the current Auth user's ID (or some user->warehouse logic)

    $warehouseId = Warehouse::where('manager_id',Auth::id())
    ->first();
    $documents = Document::whereHas('documentType', function($query) {
        $query->where('code', 'income');
    })
    ->where('to_warehouse_id', $warehouseId->id) // if the column is actually "to_warehouse_id"
    ->with(['documentItems', 'provider']) // load relationships
    ->get();



    return response()->json($documents, 200);
}


// StorageController.php

// file: StorageController.php

public function updateReceipt(Request $request, $id)
{
    Log::info($request->all());
    DB::beginTransaction();
    try {
        // find the Document
        $doc = Document::findOrFail($id);

        // update main fields
        $doc->provider_id   = $request->input('provider_id');
        $doc->document_date = $request->input('document_date');
        // if you have comments or other fields
        $doc->comments      = $request->input('comments');
        // maybe we keep the same warehouse in to_warehouse_id
        // or let user pass a new one
        // $doc->to_warehouse_id = $request->input('to_warehouse_id');
        $doc->save();

        // remove old DocumentItems
        $doc->documentItems()->delete();

        // insert new ones from 'products'
        $products = $request->input('products', []);
        foreach ($products as $p) {
            $doc->documentItems()->create([
                'product_subcard_id'  => $p['product_subcard_id']  ?? null,
                'unit_measurement'    => $p['unit_measurement']    ?? null,
                'quantity'            => $p['quantity']            ?? 0,
                'brutto'              => $p['brutto']              ?? 0,
                'netto'               => $p['netto']               ?? 0,
                'price'               => $p['price']               ?? 0,
                'total_sum'           => $p['total_sum']           ?? 0,
                'additional_expenses' => $p['additional_expenses'] ?? 0,
                'cost_price'          => $p['cost_price']          ?? 0,
            ]);
        }

        // if you have expenses in separate table, handle them
        // $doc->expenses()->delete() or something
        // then re-insert from $request->input('expenses')

        DB::commit();
        return response()->json([
            'success'  => true,
            'message'  => 'Документ обновлён',
            'document' => $doc->load('documentItems', 'provider'), // if you want to return
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}


public function deleteReceipt($id)
{
    // Delete the doc
    $doc = Document::findOrFail($id);
    $doc->delete();

    return response()->json(['success' => true, 'message' => 'Document deleted'], 200);
}

public function storageSalesBulkStore(Request $request)
{
    Log::info($request->all());

    // 1) Validate request
    $data = $request->validate([
        'sales' => 'required|array|min:1',
        'sales.*.client_id'                => 'required|integer|exists:users,id',
        'sales.*.document_date'            => 'nullable|date',
        'sales.*.items'                    => 'required|array|min:1',
        'sales.*.items.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
        'sales.*.items.*.unit_id'          => 'required|integer|exists:unit_measurements,id',
        'sales.*.items.*.quantity'         => 'required|numeric',
        'sales.*.items.*.brutto'           => 'required|numeric',
        'sales.*.items.*.netto'            => 'required|numeric',
        'sales.*.items.*.price'            => 'required|numeric',
        'sales.*.items.*.total_sum'        => 'required|numeric',
    ]);

    DB::beginTransaction();
    try {
        // A) Find manager's warehouse
        $warehouse = \App\Models\Warehouse::where('manager_id', Auth::id())->firstOrFail();
        $warehouseId = $warehouse->id;

        // B) Find 'sale' document type
        $saleType = \App\Models\DocumentType::where('code', 'sale')->firstOrFail();

        // C) Loop each sale
        foreach ($data['sales'] as $saleData) {
            // 1) Create the Document
            $document = \App\Models\Document::create([
                'document_type_id'  => $saleType->id,
                'status'           => '-',
                'client_id'        => $saleData['client_id'],
                'document_date'    => $saleData['document_date'] ?? now(),
                'from_warehouse_id'=> $warehouseId,
            ]);

            // 2) Create DocumentItems, pulling cost_price from warehouse_items
            foreach ($saleData['items'] as $item) {
                // Find the matching warehouse item to get the PER-UNIT cost_price
                $unitModel = Unit_measurement::findOrFail($item['unit_id']);
                $unitName  = $unitModel->name;

                $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $item['product_subcard_id'])
                    ->where('unit_measurement', $unitName)
                    ->first();

                if (! $whItem) {
                    throw new \Exception("No stock for product_subcard_id={$item['product_subcard_id']} (unit={$unitName}).");
                }

                // Multiply the PER-UNIT cost_price by the sold quantity
                $costPriceTotal = $whItem->cost_price * $item['quantity'];

                // Create DocumentItem
                DocumentItem::create([
                    'document_id'        => $document->id,
                    'product_subcard_id' => $item['product_subcard_id'],
                    'unit_measurement'   => $unitName,
                    'quantity'           => $item['quantity'],
                    'brutto'             => $item['brutto'],
                    'netto'              => $item['netto'],
                    'price'              => $item['price'],
                    'total_sum'          => $item['total_sum'],
                    'cost_price'      =>   $costPriceTotal,
                ]);
            }

            // 3) Subtract from warehouse stock
            foreach ($saleData['items'] as $item) {
                $unitModel = Unit_measurement::findOrFail($item['unit_id']);
                $unitName  = $unitModel->name;

                $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $item['product_subcard_id'])
                    ->where('unit_measurement', $unitName)
                    ->first();

                if (! $whItem) {
                    throw new \Exception("No stock for product_subcard_id={$item['product_subcard_id']} (unit={$unitName}).");
                }
                if ($whItem->quantity < $item['quantity']) {
                    throw new \Exception("Not enough stock. Need {$item['quantity']}, have {$whItem->quantity}.");
                }

                // Subtract the sold quantity from the warehouse
                $whItem->quantity -= $item['quantity'];
                $whItem->brutto   -= $item['brutto'];
                $whItem->netto    -= $item['netto'];
                // Optionally keep or recalc cost_price for the leftover stock, depending on your business logic
                $whItem->save();
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'All sales processed successfully!',
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error("Error in storageSalesBulkStore: ".$e->getMessage());
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}


public function updateSale(Request $request, $docId)
{
    // Validate
    $validated = $request->validate([
        'doc_type' => 'required|in:sale',    // or skip if always 'sale'
        'client_id' => 'required|integer',
        'document_date' => 'required|date',
        'items' => 'required|array|min:1',
        // 'items.*.product_subcard_id','items.*.unit_id','items.*.quantity' ...
    ]);

    DB::beginTransaction();
    try {
        // 1) Find doc
        $doc = Document::where('id',$docId)
            ->whereHas('documentType', fn($q)=>$q->where('code','sale'))
            ->firstOrFail();

        // 2) Restore old stock from existing docItems
        $oldItems = $doc->documentItems;
        foreach ($oldItems as $oldItem) {
            $whItem = WarehouseItem::where('warehouse_id',$doc->from_warehouse_id)
                ->where('product_subcard_id',$oldItem->product_subcard_id)
                ->where('unit_measurement',$oldItem->unit_measurement)
                // or 'unit_measurement_id'
                ->first();

            if (!$whItem) {
                throw new \Exception("Warehouse item not found for doc item #{$oldItem->id}");
            }
            // Return the old quantity
            $whItem->quantity += $oldItem->quantity;
            $whItem->save();
        }

        // 3) Delete old docItems
        DocumentItem::where('document_id',$doc->id)->delete();

        // 4) Update doc fields
        $doc->client_id = $validated['client_id'];
        $doc->document_date = $validated['document_date'];
        $doc->comments = $request->input('comments','');
        $doc->save();

        // 5) Insert new items & deduct stock
        //    (exactly like your creation logic)
        $warehouse = Warehouse::where('manager_id', Auth::id())->first();
        if (!$warehouse) {
            throw new \Exception("No warehouse found for user #" . Auth::id());
        }

        foreach ($validated['items'] as $itemData) {
            $productId = $itemData['product_subcard_id'];
            $unitId = $itemData['unit_id'];
            $qty = floatval($itemData['quantity'] ?? 0);

            $unitModel = Unit_measurement::findOrFail($unitId);
            $whItem = WarehouseItem::where('warehouse_id', $warehouse->id)
                ->where('product_subcard_id', $productId)
                ->where('unit_measurement', $unitModel->name)
                ->firstOrFail();

            if ($whItem->quantity < $qty) {
                throw new \Exception("Not enough stock for product #{$productId}");
            }

            // ratio calculations, etc.
            $ratio = $whItem->quantity > 0 ? ($qty / $whItem->quantity) : 0;
            $soldBrutto = round($whItem->brutto * $ratio,2);
            $soldNetto = round($whItem->netto * $ratio,2);
            $soldSum = round($whItem->total_sum * $ratio,2);
            $soldAddExp = round($whItem->additional_expenses * $ratio,2);

            // deduct from wh
            $whItem->quantity -= $qty;
            $whItem->save();

            // create docItem
            DocumentItem::create([
                'document_id'=>$doc->id,
                'product_subcard_id'=>$productId,
                'unit_measurement_id'=>$unitModel->id,
                'quantity'=>$qty,
                'brutto'=>$soldBrutto,
                'netto'=>$soldNetto,
                'price'=>$whItem->price,
                'total_sum'=>$soldSum,
                'cost_price'=>$whItem->cost_price,
                'additional_expenses'=>$soldAddExp
            ]);
        }

        DB::commit();

        return response()->json([
            'success'=>true,
            'message'=>"Sale #{$docId} updated successfully!"
        ],200);

    } catch(\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success'=>false,
            'error'=>$e->getMessage()
        ],500);
    }
}


public function deleteSale($docId)
{
    DB::beginTransaction();
    try {
        // 1) Find Document
        $doc = Document::where('id', $docId)->whereHas('documentType', function($q){
            $q->where('code','sale');
        })->firstOrFail();

        // 2) Only proceed if doc->status is not "final" or something.
        //    (Optional: If you allow deleting completed docs, skip)
        // if ($doc->status === 'some_final_status') {
        //   throw new \Exception("Cannot delete a finalized sale!");
        // }

        // 3) For each DocumentItem, restore stock
        $docItems = $doc->documentItems; // a Collection of DocumentItem
        foreach ($docItems as $item) {
            // Find the relevant WarehouseItem
            $warehouseId = $doc->from_warehouse_id; // The sale warehouse
            $productSubId = $item->product_subcard_id;
            $unitName = $item->unit_measurement;
            // or if you used 'unit_measurement_id', you'll have to find the name from Unit_measurement.

            $whItem = WarehouseItem::where('warehouse_id',$warehouseId)
                ->where('product_subcard_id',$productSubId)
                ->where('unit_measurement',$unitName)
                ->first();

            if (!$whItem) {
                throw new \Exception("Warehouse item not found for doc item #{$item->id}");
            }

            // Return quantity
            $whItem->quantity += $item->quantity;
            $whItem->save();
        }

        // 4) Delete docItems
        DocumentItem::where('document_id',$doc->id)->delete();

        // 5) Delete doc
        $doc->delete();

        DB::commit();

        return response()->json([
            'success'=>true,
            'message'=>"Sale document #{$docId} deleted successfully!"
        ], 200);

    } catch(\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success'=>false,
            'error'=>$e->getMessage()
        ], 500);
    }
}

    public function getReport(Request $request)
    {
        // Get date range from query, e.g. ?date_from=2025-01-01&date_to=2025-01-31
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // Grab the current user's ID
        $managerId = auth()->id(); // or Auth::id()

        // Optional: fetch document type IDs, if you need them
        $typeIncome   = DocumentType::where('code', 'income')->value('id');
        $typeTransfer = DocumentType::where('code', 'transfer')->value('id');
        $typeSale     = DocumentType::where('code', 'sale')->value('id');
        $typeWriteOff = DocumentType::where('code', 'write_off')->value('id');

        // Main query: only show warehouses where manager_id = current user
        $report = DB::table('warehouses AS wh')
            ->join('warehouse_items AS wi', 'wi.warehouse_id', '=', 'wh.id')
            ->join('product_sub_cards AS psc', 'psc.id', '=', 'wi.product_subcard_id')
            // Documents (leftJoin)
            ->leftJoin('documents AS d', function($join) {
                $join->on('d.to_warehouse_id', '=', 'wh.id')
                    ->orOn('d.from_warehouse_id', '=', 'wh.id');
            })
            ->leftJoin('document_types AS dt', 'dt.id', '=', 'd.document_type_id')
            ->leftJoin('document_items AS di', function($join) {
                $join->on('di.document_id', '=', 'd.id');
                $join->on('di.product_subcard_id', '=', 'psc.id');
            })
            ->select(
                'wh.id AS warehouse_id',
                'wh.name AS warehouse_name',
                'psc.id AS product_id',
                'psc.name AS product_name',
                'wi.quantity AS current_quantity',
                'wi.cost_price AS current_cost_price',

                // Total inbound
                DB::raw("
                    SUM(
                        CASE
                        WHEN (dt.code = 'income' AND d.to_warehouse_id = wh.id)
                            OR (dt.code = 'transfer' AND d.to_warehouse_id = wh.id)
                        THEN di.quantity
                        ELSE 0
                        END
                    ) AS total_inbound
                "),

                // Total outbound
                DB::raw("
                    SUM(
                        CASE
                        WHEN (dt.code = 'sale'       AND d.from_warehouse_id = wh.id)
                            OR (dt.code = 'write_off' AND d.from_warehouse_id = wh.id)
                            OR (dt.code = 'transfer'  AND d.from_warehouse_id = wh.id)
                        THEN di.quantity
                        ELSE 0
                        END
                    ) AS total_outbound
                ")
            )
            // Restrict to the currently-authenticated manager
            ->where('wh.manager_id', '=', $managerId)

            // Optionally filter by document date range
            ->when($dateFrom && $dateTo, function($query) use ($dateFrom, $dateTo) {
                return $query->whereBetween('d.document_date', [$dateFrom, $dateTo]);
            })
            ->groupBy('wh.id', 'psc.id', 'wi.quantity', 'wi.cost_price', 'wh.name', 'psc.name')
            ->orderBy('wh.id')
            ->orderBy('psc.id')
            ->get();

        // Transform results to add remainder, remainder_value, etc.
        $report->transform(function ($row) {
            $inbound      = $row->total_inbound ?? 0;
            $outbound     = $row->total_outbound ?? 0;
            $currentStock = $inbound - $outbound;

            $costPrice    = $row->current_cost_price ?? 0;
            $value        = $currentStock * $costPrice;

            $row->remainder       = $currentStock;
            $row->cost_price      = $costPrice;
            $row->remainder_value = $value;

            return $row;
        });

        return response()->json($report);
    }

    public function getReceiptWithReferences($docId)
    {

        $document = Document::with([
            'documentItems',
            'expenses',
            'provider',
            'documentType',
        ])->findOrFail($docId);

        $references = [
            'providers'         => Provider::all(),
            'warehouses'        => Warehouse::all(),
            'product_sub_cards' => ProductSubCard::all(),
            'unit_measurements' => Unit_measurement::all(),
            'expenses'          => Expense::all(),
        ];

        return response()->json([
            'document'   => $document,
            'references' => $references,
        ], 200);
    }

}
