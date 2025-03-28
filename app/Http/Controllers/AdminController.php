<?php

namespace App\Http\Controllers;

use App\Models\AdminCashes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\PriceRequest;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Expense;
use Illuminate\Support\Facades\Log;
use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\User;
use App\Models\WarehouseItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Basic Product Creation / Updates
    |--------------------------------------------------------------------------
    */

    /**
     * Quick product creation (example)
     */
    public function create_product(Request $request)
    {
        return ProductCard::create($request->query->all());
    }

    /**
     * Store a ProductCard, with optional photo upload
     */
    public function storeProduct(Request $request)
    {
        $request->validate([
            'name_of_products' => 'required|string',
            'type' => 'required|string',
            'photo_product' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        $data = $request->all();
        if ($request->hasFile('photo_product')) {
            // Store in 'public/products/photos'
            $imagePath = $request->file('photo_product')->store('products/photos', 'public');
            $data['photo_product'] = $imagePath;
        }

        ProductCard::create($data);

        return response()->json(['success' => 'Product created successfully.'], 201);
    }

    /**
     * Update an existing ProductCard
     */
    public function update(Request $request, ProductCard $product)
    {
        $request->validate([
            'name_of_products' => 'required|string',
            'type' => 'required|string',
            'photo_product' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        // Check if a new file is uploaded
        if ($request->hasFile('photo_product')) {
            $imagePath = $request->file('photo_product')->store('products/photos', 'public');
            $data['photo_product'] = $imagePath;

            // Optionally delete old photo
            if ($product->photo_product && Storage::disk('public')->exists($product->photo_product)) {
                Storage::disk('public')->delete($product->photo_product);
            }
        }

        $product->update($data);

        return response()->json(['success' => 'Product updated successfully.']);
    }

    /**
     * Example "sell" method for a subcard
     */
    public function sellProduct(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|integer|min:1',
        ]);

        $product = ProductSubCard::findOrFail($productId);

        if ($product->quantity < $request->quantity) {
            return response()->json(['error' => 'Not enough stock available'], 400);
        }

        // Reduce the subcard quantity
        $product->quantity -= $request->quantity;
        $product->save();

        // Create a 'sale' record (example—your real logic may differ)
        $sale = new ProductSubCard();
        $sale->product_id = $product->id;
        $sale->quantity_sold = $request->quantity;
        $sale->price_at_sale = $request->price;
        $sale->save();

        return response()->json([
            'message' => 'Product sold successfully',
            'sale' => $sale
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Price Offer Requests
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new Offer Request
     */
    public function createOfferRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:basic_products_prices,id',
            'unit_measurement' => 'required|string',
            'amount' => 'required|integer',
            'price' => 'required|numeric',
            'choice_status' => 'nullable|string',
            'address_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $priceRequest = PriceRequest::create($request->all());

        return response()->json([
            'message' => 'Offer request created successfully',
            'data' => $priceRequest
        ], 201);
    }

    /**
     * Get all Offer Requests
     */
    public function getOfferRequests()
    {
        $requests = PriceRequest::with(['user', 'product'])->get();
        return response()->json($requests, 200);
    }

    /**
     * Get a specific Offer Request
     */
    public function getOfferRequest($id)
    {
        $request = PriceRequest::with(['user', 'product'])->find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        return response()->json($request, 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Warehouse
    |--------------------------------------------------------------------------
    */



    /**
     * веб версия приход товаров
     */
    public function storeIncome(Request $request)
{
    // Посмотрим, что пришло:
    Log::info($request->all());

    // Массив товаров
    $products = $request->input('products', []);
    // Массив расходов
    $expenses = $request->input('expenses', []);

    // ID поставщика (из фронта)
    $providerId = $request->input('provider_id');
    // Дата документа (из фронта)
    $docDate = $request->input('document_date');
    // Выбранный "Склад поступления" (заменяем прежний assigned_user_id)
    $warehouseId = $request->input('assigned_warehouse_id'); // может быть null

    if (empty($products)) {
        return response()->json(['success'=>false, 'error'=>'No products given'], 422);
    }

    DB::beginTransaction();
    try {
        // Ищем запись DocumentType, где code='income'
        $docType = DocumentType::where('code', 'income')->firstOrFail();

        // Создаём «шапку» документа
        $doc = Document::create([
            'document_type_id' => $docType->id,
            'status'           => '+',           // Приход => статус "+"
            'provider_id'      => $providerId,
            'document_date'    => $docDate ?? now(),
            'comments'         => $request->input('comments'),
            // Вместо destination_user_id используем to_warehouse_id
            'to_warehouse_id'  => $warehouseId,
        ]);

        // Сохраняем строки (DocumentItem) — историческая запись о поступлении
        foreach ($products as $item) {
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

            // Теперь добавим/обновим запись(и) в WarehouseItem (текущие остатки на складе)
            if ($warehouseId) {
                // Находим или создаём WarehouseItem для этого склада и продукта
                $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $item['product_subcard_id'] ?? null)
                    ->where('unit_measurement', $item['unit_measurement'] ?? null)
                    ->first();

                if (!$whItem) {
                    // Создаём новую запись
                    $whItem = new WarehouseItem();
                    $whItem->warehouse_id       = $warehouseId;
                    $whItem->product_subcard_id = $item['product_subcard_id']  ?? null;
                    $whItem->unit_measurement   = $item['unit_measurement']    ?? null;
                    $whItem->quantity           = 0;
                    $whItem->brutto             = 0;
                    $whItem->netto              = 0;
                    $whItem->total_sum          = 0;
                }

                // Логика обновления (сложение, если хотим хранить остаток)
                $whItem->quantity            += ($item['quantity']            ?? 0);
                $whItem->brutto             += ($item['brutto']              ?? 0);
                $whItem->netto              += ($item['netto']               ?? 0);
                // Текущая цена/себестоимость может быть tricky:
                // - Можем установить последнюю,
                // - или высчитывать среднюю,
                // - или хранить отдельно. Для примера просто перезаписываем:
                $whItem->price               = $item['price']               ?? 0;
                // Аналогично для total_sum — можно суммировать:
                $whItem->total_sum          += ($item['total_sum']           ?? 0);
                // additional_expenses / cost_price — тоже могут потребовать средней арифметики, но тут упростим:
                $whItem->additional_expenses = $item['additional_expenses']  ?? 0;
                $whItem->cost_price          = $item['cost_price']           ?? 0;

                $whItem->save();
            }
        }

        // Обновляем или связываем расходы (примерно как у вас было)
        foreach ($expenses as $exp) {
            $existingExpense = Expense::findOrFail($exp['expense_id']);
            $existingExpense->update([
                'document_id' => $doc->id,   // теперь привязываем к этому документу
                'name'        => $exp['name']   ?? 'Расход',
                'amount'      => $exp['amount'] ?? 0,
            ]);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Документ (Приход) сохранён'], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error'=>$e->getMessage()], 500);
    }
}


// мобильнаяя версия админке приход товаров
public function storeIncomes(Request $request)
{
    // 1) Retrieve the entire array of “receivings” from the request
    $receivings = $request->input('receivings', []);

    // 2) If nothing is sent, throw a validation error
    if (empty($receivings)) {
        return response()->json([
            'success' => false,
            'error'   => 'No receiving data was provided'
        ], 422);
    }

    // 3) Wrap everything in a DB transaction so that if anything fails,
    //    all changes will be rolled back
    DB::beginTransaction();
    try {
        // Find the DocumentType record for an “income” doc
        $docType = DocumentType::where('code', 'income')->firstOrFail();

        // 4) Loop through each “receiving” item
        foreach ($receivings as $receiving) {

            // Extract data needed to create a Document
            $providerId  = $receiving['provider_id']     ?? null;
            $docDate     = $receiving['document_date']   ?? now();
            $warehouseId = $receiving['warehouse_id']    ?? null;
            $products    = $receiving['products']        ?? [];
            $expenses    = $receiving['expenses']        ?? [];
            $comments    = $receiving['comments']        ?? null;

            // Make sure we have at least some products for this document
            if (empty($products)) {
                // Depending on your logic, you could skip this receiving
                // or throw an error. Here, let’s throw an error:
                throw new \Exception("No products specified for one of the receivings");
            }

            // 5) Create the “header” row in `documents` table
            $doc = Document::create([
                'document_type_id' => $docType->id,
                'status'           => '+',  // '+' to indicate income
                'provider_id'      => $providerId,
                'document_date'    => $docDate,
                'comments'         => $comments,
                'to_warehouse_id'  => $warehouseId,
            ]);

            // 6) For each product, create a DocumentItem and update WarehouseItem
            foreach ($products as $item) {
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

                // If warehouseId is present, update or create a WarehouseItem
                if ($warehouseId) {
                    $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                        ->where('product_subcard_id', $item['product_subcard_id'] ?? null)
                        ->where('unit_measurement', $item['unit_measurement'] ?? null)
                        ->first();

                    // If no existing record found, create a new blank one
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

                    // Accumulate the new quantities and sums
                    $whItem->quantity    += ($item['quantity']  ?? 0);
                    $whItem->brutto     += ($item['brutto']    ?? 0);
                    $whItem->netto      += ($item['netto']     ?? 0);
                    // Overwrite or recalculate the price as needed
                    $whItem->price       = ($item['price']     ?? 0);
                    // Summation for total_sum
                    $whItem->total_sum  += ($item['total_sum'] ?? 0);

                    // Optional: additional_expenses & cost_price
                    // In practice you might want average calculations; here we just overwrite
                    $whItem->additional_expenses = ($item['additional_expenses'] ?? 0);
                    $whItem->cost_price          = ($item['cost_price']          ?? 0);

                    $whItem->save();
                }
            }

            // 7) Handle expenses
            //    Example approach (create new expense entries):
            foreach ($expenses as $exp) {
                // If your front provides an 'expense_id', you could do findOrFail($exp['expense_id']) here
                // but in your sample data we have only name and amount. So we just create a new record.
                Expense::create([
                    'document_id' => $doc->id,
                    'name'        => $exp['name']   ?? 'Расход',
                    'amount'      => $exp['amount'] ?? 0,
                ]);
            }
        }

        // 8) If we reach here without errors, commit the transaction
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'All receiving documents stored successfully'
        ], 201);

    } catch (\Throwable $e) {
        // 9) Roll everything back if something fails
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage()
        ], 500);
    }
}


// мобильная версия админке списание товаров
public function storeWriteOff(Request $request)
{
    // Массив "write_offs" (каждый элемент = один документ)
    $writeOffs = $request->input('write_offs', []);
    if (empty($writeOffs)) {
        return response()->json([
            'success' => false,
            'error'   => 'No write_offs array provided'
        ], 422);
    }

    DB::beginTransaction();
    try {
        // 1) Ищем тип документа "write_off" (достаточно один раз)
        $docType = DocumentType::where('code', 'write_off')->firstOrFail();

        // Будем хранить ID созданных документов, чтобы вернуть их все разом
        $createdDocIds = [];

        // 2) Перебираем каждую «порцию» списания (каждый элемент массива write_offs)
        foreach ($writeOffs as $wo) {
            // Валидируем данные внутри каждого "wo"
            $validator = Validator::make($wo, [
                'warehouse_id'  => 'required|integer',
                'document_date' => 'required|date',
                'items'         => 'required|array|min:1',
            ]);

            if ($validator->fails()) {
                throw new \Exception(
                    "Validation error: "
                    . json_encode($validator->errors(), JSON_UNESCAPED_UNICODE)
                );
            }

            $validated = $validator->validated();

            $warehouseId = $validated['warehouse_id'];
            $docDate     = $validated['document_date'];
            $items       = $validated['items'];

            // 3) Создаём документ "Списание"
            $doc = Document::create([
                'document_type_id'  => $docType->id,
                'status'            => '-',        // признак "убыло со склада"
                'from_warehouse_id' => $warehouseId,
                'to_warehouse_id'   => 0,          // или null
                'document_date'     => $docDate,
                'comments'          => "Списание со склада #$warehouseId",
            ]);

            // В этот массив соберём строки для DocumentItems, чтобы потом одним циклом добавить
            $writtenOffItems = [];

            // 4) Для каждой строки списываем товар со склада
            foreach ($items as $row) {
                $prodId   = $row['product_subcard_id'];
                $qty      = $row['quantity']         ?? 0;
                $uMeasure = $row['unit_measurement'] ?? '';

                // Находим позицию на складе
                $whItem = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $prodId)
                    ->where('unit_measurement', $uMeasure)
                    ->first();

                // Проверяем, достаточно ли остатков
                if (!$whItem) {
                    throw new \Exception(
                        "Склад #$warehouseId не имеет товара product_subcard_id=$prodId (ед. изм '$uMeasure')"
                    );
                }
                if ($whItem->quantity < $qty) {
                    throw new \Exception(
                        "Недостаточно товара (ID=$prodId) на складе $warehouseId "
                        ."(требуется $qty, есть {$whItem->quantity})."
                    );
                }

                // Старые значения (до списания)
                $oldQty    = $whItem->quantity;
                $oldBrutto = $whItem->brutto;
                $oldNetto  = $whItem->netto;
                $oldPrice  = $whItem->price;
                $oldSum    = $whItem->total_sum;
                $oldCost   = $whItem->cost_price;
                $oldAddExp = $whItem->additional_expenses;

                // Определяем пропорцию (сколько % списываем)
                $ratio = $oldQty > 0 ? ($qty / $oldQty) : 0;

                // Сколько "ушло" (брютто, нетто, сумма, расходы)
                $woBrutto = round($oldBrutto * $ratio, 2);
                $woNetto  = round($oldNetto  * $ratio, 2);
                $woSum    = round($oldSum    * $ratio, 2);
                $woAddExp = round($oldAddExp * $ratio, 2);

                // Обновляем остаток
                $whItem->quantity -= $qty;
                if ($whItem->quantity > 0) {
                    // Новый ratio для остатка
                    $newRatio = $whItem->quantity / $oldQty;

                    $whItem->brutto             = round($oldBrutto * $newRatio, 2);
                    $whItem->netto              = round($oldNetto  * $newRatio, 2);
                    $whItem->total_sum          = round($oldSum    * $newRatio, 2);
                    $whItem->additional_expenses= round($oldAddExp * $newRatio, 2);
                } else {
                    // Полный ноль
                    $whItem->quantity = 0;
                    $whItem->brutto   = 0;
                    $whItem->netto    = 0;
                    $whItem->total_sum= 0;
                    $whItem->additional_expenses= 0;
                }
                $whItem->save();

                // (необязательно) net_unit_weight для списанной части:
                $netUnitWeight = ($qty > 0)
                    ? round($woNetto / $qty, 4)
                    : 0;

                // Сохраняем информацию о "списанной части" (самом факте списания)
                $writtenOffItems[] = [
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $uMeasure,
                    'quantity'            => $qty,       // СКОЛЬКО списали
                    'brutto'              => $woBrutto,
                    'netto'               => $woNetto,
                    'price'               => $oldPrice,
                    'total_sum'           => $woSum,
                    'additional_expenses' => $woAddExp,
                    'cost_price'          => $oldCost,
                    'net_unit_weight'     => $netUnitWeight, // если нужно
                ];
            }

            // 5) Создаём строки DocumentItem
            foreach ($writtenOffItems as $wi) {
                DocumentItem::create(array_merge($wi, [
                    'document_id' => $doc->id,
                ]));
            }

            // Запоминаем созданный doc.id
            $createdDocIds[] = $doc->id;
        }

        DB::commit();
        return response()->json([
            'success'      => true,
            'message'      => 'Списание(я) успешно выполнено!',
            'document_ids' => $createdDocIds,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}




    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */

    public function getProviders()
    {
        return response()->json(Provider::all(), 200);
    }

    public function storeProvider(Request $request)
    {
        $request->validate(['name' => 'required']);
        $provider = Provider::create($request->only('name'));
        return response()->json($provider, 201);
    }

    public function updateProvider(Request $request, Provider $provider)
    {
        $provider->update($request->all());
        return response()->json($provider, 200);
    }

    public function destroyProvider(Provider $provider)
    {
        $provider->delete();
        return response()->json(['message' => 'Provider deleted'], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Client Users
    |--------------------------------------------------------------------------
    */

    public function getClientUsers()
    {
        try {
            $clientUsers = User::whereHas('roles', function ($query) {
                $query->where('name', 'client');
            })->get(['id', 'first_name', 'last_name', 'whatsapp_number']);

            return response()->json($clientUsers, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch client users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Operations History
    |--------------------------------------------------------------------------
    */

    public function fetchOperationsHistory()
    {
        $productCards = DB::table('product_cards')
            ->select(
                'id',
                DB::raw('CAST(name_of_products AS CHAR) as operation'),
                'created_at',
                DB::raw("'Карточка товара' as type")
            )
            ->get();

        $productSubcards = DB::table('product_sub_cards')
            ->select(
                'id',
                DB::raw('CAST(name AS CHAR) as operation'),
                'created_at',
                DB::raw("'Подкарточка товара' as type")
            )
            ->get();



        $unitMeasurements = DB::table('unit_measurements')
            ->select(
                'id',
                DB::raw('CAST(name AS CHAR) as operation'),
                'created_at',
                DB::raw("'Единица измерения' as type")
            )
            ->get();

        $roles = DB::table('roles')
            ->select(
                'id',
                DB::raw('CAST(name AS CHAR) as operation'),
                'created_at',
                DB::raw("'Присвоить роль' as type")
            )
            ->get();

        $addresses = DB::table('addresses')
            ->select(
                'id',
                DB::raw('CAST(name AS CHAR) as operation'),
                'created_at',
                DB::raw("'Присвоить адрес' as type")
            )
            ->get();


        $providers = DB::table('providers')
            ->select(
                'id',
                DB::raw('CAST(name AS CHAR) as operation'),
                'created_at',
                DB::raw("'Поставщик' as type")
            )
            ->get();
        $expenses = DB::table('expenses')
        ->select(
            'id',
            DB::raw('CAST(name AS CHAR) as operation'),
            'created_at',
            DB::raw("'Расход' as type")  // Or "expense" in Russian
        )
        ->get();

        // Combine and sort operations by creation date
        $operations = $productCards
            ->concat($productSubcards)
            ->concat($unitMeasurements)
            ->concat($roles)
            ->concat($addresses)
            ->concat($providers)
            ->concat($expenses)

            ->sortByDesc('created_at')
            ->values();

        return response()->json($operations, 200);
    }

    /**
     * Update any reference by type
     */
    public function updateOperation(Request $request, $id, $type)
    {
        try {
            $operation = $this->findOperationByType($type, $id);
            $validated = $this->validateOperationFields($request, $type);

            $operation->update($validated);

            return response()->json(['message' => 'Operation updated successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Operation not found'], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a reference by type
     */
    public function deleteOperation($id, $type)
    {
        Log::info([$type, $id]);
        try {
            $operation = $this->findOperationByType($type, $id);
            $operation->delete();

            return response()->json(['message' => 'Operation deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Operation not found'], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper to find and validate an operation by type
    |--------------------------------------------------------------------------
    */
    private function findOperationByType($type, $id)
    {
        switch ($type) {
            case 'Карточка товара':
                return ProductCard::findOrFail($id);
            case 'Подкарточка товара':
                return ProductSubCard::findOrFail($id);
            case 'Продажа':
                return Sale::findOrFail($id);
            case 'Ценовое предложение':
                return PriceRequest::findOrFail($id);
            default:
                throw new InvalidArgumentException('Invalid operation type');
        }
    }

    private function validateOperationFields(Request $request, $type)
    {
        // Example logic: you can expand this or make it dynamic
        switch ($type) {
            case 'Карточка товара':
                return $request->validate([
                    'name_of_products' => 'required|string',
                    'description' => 'nullable|string',
                    // add other relevant fields
                ]);
            case 'Подкарточка товара':
                return $request->validate([
                    'name' => 'required|string',
                    'brutto' => 'nullable|numeric',
                    'netto' => 'nullable|numeric',
                ]);
            case 'Продажа':
                return $request->validate([
                    'amount' => 'required|numeric',
                ]);
            case 'Ценовое предложение':
                return $request->validate([
                    'amount' => 'required|numeric',
                ]);
            default:
                throw new InvalidArgumentException('Invalid operation type');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Cashes
    |--------------------------------------------------------------------------
    */
    public function adminCashes()
    {
        $adminCashes = AdminCashes::all(['id', 'name', 'IBAN']);
        return response()->json($adminCashes, 200);
    }




}

