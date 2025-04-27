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
use App\Models\ReferenceItem;
use App\Models\Sale;
use App\Models\User;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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
    Log::info($request->all());

    $products   = $request->input('products',  []);
    $expensesIn = $request->input('expenses',  []);

    if (empty($products)) {
        return response()->json(['success' => false, 'error' => 'No products given'], 422);
    }

    DB::beginTransaction();
    try {
        /* ───── 1. «Шапка» документа ───── */
        $docType = DocumentType::where('code', 'income')->firstOrFail();

        $doc = Document::create([
            'document_type_id' => $docType->id,
            'status'           => '+',
            'provider_id'      => $request->provider_id,
            'document_date'    => $request->document_date ?? now(),
            'comments'         => $request->comments,
            'to_warehouse_id'  => $request->assigned_warehouse_id,
        ]);

        /* ───── 2. Товарные позиции ───── */
        foreach ($products as $p) {
            DocumentItem::create([
                'document_id'         => $doc->id,
                'product_subcard_id'  => $p['product_subcard_id'] ?? null,
                'unit_measurement'    => $p['unit_measurement']   ?? null,
                'quantity'            => $p['quantity']           ?? 0,
                'brutto'              => $p['brutto']             ?? 0,
                'netto'               => $p['netto']              ?? 0,
                'price'               => $p['price']              ?? 0,
                'total_sum'           => $p['total_sum']          ?? 0,
                'additional_expenses' => $p['additional_expenses']?? 0,
                'cost_price'          => $p['cost_price']         ?? 0,
                'net_unit_weight'     => ($p['quantity'] ?? 0) > 0
                                        ? $p['netto'] / $p['quantity']
                                        : 0,
            ]);

            /* — текущие остатки на складе (WarehouseItem) — */
            if ($request->assigned_warehouse_id) {
                $wh = WarehouseItem::firstOrNew([
                    'warehouse_id'       => $request->assigned_warehouse_id,
                    'product_subcard_id' => $p['product_subcard_id'] ?? null,
                    'unit_measurement'   => $p['unit_measurement']   ?? null,
                ]);

                $wh->quantity    += $p['quantity'] ?? 0;
                $wh->brutto      += $p['brutto']   ?? 0;
                $wh->netto       += $p['netto']    ?? 0;
                $wh->total_sum   += $p['total_sum']?? 0;
                $wh->price        = $p['price']    ?? 0;
                $wh->cost_price   = $p['cost_price'] ?? 0;
                $wh->save();
            }
        }

        /* ───── 3. Доп. расходы ───── */
        foreach ($expensesIn as $e) {
            $providerId = $e['provider_id'] ?? null;
            $baseItem   = ReferenceItem::find($e['expense_id']);
            if (!$baseItem) { continue; }

            /* 3-a. если тот же поставщик → update value */
            if (($baseItem->provider_id ?? null) === $providerId) {
                $baseItem->update(['value' => $e['amount'] ?? 0]);
                $refId = $baseItem->id;
            }
            /* 3-b. другой поставщик → клон либо уже-существующая строка */
            else {
                $clone = ReferenceItem::firstOrCreate(
                    [
                        'reference_id' => $baseItem->reference_id,
                        'name'         => $baseItem->name,
                        'provider_id'  => $providerId,
                    ],
                    [
                        'description' => $baseItem->description,
                        'type'        => $baseItem->type,
                        'country'     => $baseItem->country,
                        'value'       => $e['amount'] ?? 0,
                    ]
                );
                // если строка существовала – обновим value
                if (!$clone->wasRecentlyCreated) {
                    $clone->update(['value' => $e['amount'] ?? 0]);
                }
                $refId = $clone->id;
            }

            /* 3-c. pivot-связь document ↔ expense */
            Expense::firstOrCreate([
                'document_id'       => $doc->id,
                'reference_item_id' => $refId,
                'provider_id'       => $providerId,
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Документ (Приход) сохранён',
            'doc_id'  => $doc->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// мобильнаяя версия админке приход товаров

public function storeIncomes(Request $request): JsonResponse
{
    Log::info($request->all());

    $products   = $request->input('products',  []);
    $expensesIn = $request->input('expenses',  []);

    if (!$products) {
        return response()->json(['success'=>false,'error'=>'No products given'],422);
    }

    DB::beginTransaction();
    try {
        /*─── 1. «Шапка» ───*/
        $docType = DocumentType::where('code','income')->firstOrFail();

        $doc = Document::create([
            'document_type_id' => $docType->id,
            'status'           => '+',
            'provider_id'      => $request->providerId,
            'document_date'    => Carbon::parse($request->docDate)->toDateString(),
            'comments'         => $request->comments,
            'to_warehouse_id'  => $request->assigned_warehouse_id,
        ]);

        /*─── 2. Товары ───*/
        /* ─── 2. Товарные позиции ─── */
foreach ($products as $row) {

    /* 1. ID под-карточки и ID единицы */
    $productId = data_get($row,'product.id');       // subcard id
    $unitId    = data_get($row,'unit.id');          // ID справочника ReferenceItem

    /* 2. Читаем НАЗВАНИЕ из БД, даже если клиент уже прислал name */
    $unitName  = data_get($row,'unit.name');
    if (!$unitName) {
        $unitName = ReferenceItem::findOrFail($unitId)->name;
    }

    /* 3. Количество */
    $qty = (float) ( $row['qtyTare'] ?? $row['qty'] ?? 0 );

    /* 4. Записываем строку документа  ─ unit_measurement = NAME! */
    DocumentItem::create([
        'document_id'        => $doc->id,
        'product_subcard_id' => $productId,
        'unit_measurement'   => $unitName,          // ← только название
        'quantity'           => $qty,
        'brutto'             => $row['brutto']             ?? 0,
        'netto'              => $row['netto']              ?? 0,
        'price'              => $row['price']              ?? 0,
        'total_sum'          => $row['total_sum']          ?? 0,
        'additional_expenses'=> $row['additional_expenses']?? 0,
        'cost_price'         => $row['cost_price']         ?? 0,
        'net_unit_weight'    => $qty>0 ? ($row['netto']/$qty) : 0,
    ]);

    /* 5. Остаток на складе — ищем/создаём только по NAME */
    $wh = WarehouseItem::firstOrNew([
        'warehouse_id'       => $request->assigned_warehouse_id,
        'product_subcard_id' => $productId,
        'unit_measurement'   => $unitName,          // ← опять название
    ]);

    $wh->quantity            += $qty;
    $wh->brutto              += $row['brutto']   ?? 0;
    $wh->netto               += $row['netto']    ?? 0;
    $wh->total_sum           += $row['total_sum']?? 0;
    $wh->price                = $row['price']    ?? 0;
    $wh->cost_price           = $row['cost_price'] ?? 0;
    $wh->additional_expenses += $row['additional_expenses'] ?? 0;
    $wh->save();
}

        /*─── 3. Доп-расходы (без изменений) ───*/
        foreach ($expensesIn as $e) {
            $expenseId  = data_get($e,'name.id');
            $providerId = data_get($e,'provider.id');
            $amount     = $e['amount'] ?? 0;

            $base = ReferenceItem::find($expenseId);
            if (!$base) { continue; }

            $refItem = ($base->provider_id ?? null) === $providerId
                       ? tap($base)->update(['value'=>$amount])
                       : ReferenceItem::firstOrCreate(
                            ['reference_id'=>$base->reference_id,'name'=>$base->name,'provider_id'=>$providerId],
                            array_merge($base->only(['description','type','country']),['value'=>$amount])
                         );

            Expense::firstOrCreate([
                'document_id'       => $doc->id,
                'reference_item_id' => $refItem->id,
                'provider_id'       => $providerId,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true,'message'=>'Приход сохранён','doc_id'=>$doc->id],201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}

public function updateIncomes(Request $request, Document $document)
{
    /* ---------- 0. Проверки ---------- */

    // документ должен иметь тип
    if (!$document->documentType) {
        return response()->json([
            'success' => false,
            'error'   => 'У документа не указан тип (document_type_id)'
        ], 400);
    }

    // и быть именно «приходом»
    if ($document->documentType->code !== 'income') {
        return response()->json([
            'success' => false,
            'error'   => 'Документ не является приходом (income)'
        ], 400);
    }

    $products   = $request->input('products',  []);
    $expensesIn = $request->input('expenses',  []);

    if (empty($products)) {
        return response()->json([
            'success' => false,
            'error'   => 'No products given'
        ], 422);
    }

    /* ---------- 1. Транзакция ---------- */
    DB::beginTransaction();
    try {
        /* A. откатываем старые остатки */
        $this->revertWarehouseBalances($document);

        /* B. чистим старые строки */
        $document->items()->delete();      // товары
        $document->expenses()->delete();   // расходы

        /* C. обновляем «шапку» */
        $document->update([
            'provider_id'     => $request->providerId,
            'document_date'   => $request->docDate ?? now(),
            'comments'        => $request->comments,
            'to_warehouse_id' => $request->assigned_warehouse_id,
        ]);

        /* D. добавляем новые товары и остатки */
        $this->insertItems(
            $document,
            $products,
            $request->assigned_warehouse_id
        );

        /* E. добавляем новые расходы */
        $this->syncExpenses($document, $expensesIn);

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Документ обновлён',
            'doc_id'  => $document->id
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage()
        ], 500);
    }
}
/* -----------------------------------------------------------------
 |  Откатываем складские остатки
 * ----------------------------------------------------------------*/
private function revertWarehouseBalances(Document $doc): void
{
    foreach ($doc->items as $item) {

        // если в документе не указан склад – ничего не делаем
        if (!$doc->to_warehouse_id) {
            continue;
        }

        $wh = WarehouseItem::where([
                  'warehouse_id'       => $doc->to_warehouse_id,
                  'product_subcard_id' => $item->product_subcard_id,
                  'unit_measurement'   => $item->unit_measurement,
              ])->first();

        if ($wh) {
            // обычный decrement для каждой колонки
            $wh->quantity   -= $item->quantity;
            $wh->brutto     -= $item->brutto;
            $wh->netto      -= $item->netto;
            $wh->total_sum  -= $item->total_sum;

            // не забываем сохранить
            $wh->save();
        }
    }
}

/* -----------------------------------------------------------------
 |  Вставляем позиции и корректируем склад
 * ----------------------------------------------------------------*/
private function insertItems(Document $doc, array $products, ?int $warehouseId): void
{
    foreach ($products as $p) {

        $productId = data_get($p, 'product.id');
        $unitId    = data_get($p, 'unit.id');
        $rawQty    = data_get($p, 'qtyTare');
        $qty       = ($rawQty === '' || $rawQty === null) ? 0 : (float)$rawQty;

        /* --- создаём строку документа --- */
        $doc->items()->create([
            'product_subcard_id'  => $productId,
            'unit_measurement'    => $unitId,
            'quantity'            => $qty,
            'brutto'              => $p['brutto']             ?? 0,
            'netto'               => $p['netto']              ?? 0,
            'price'               => $p['price']              ?? 0,
            'total_sum'           => $p['total_sum']          ?? 0,
            'additional_expenses' => $p['additional_expenses']?? 0,
            'cost_price'          => $p['cost_price']         ?? 0,
            'net_unit_weight'     => $qty > 0 ? ($p['netto'] / $qty) : 0,
        ]);

        /* --- корректируем остатки склада (если указан) --- */
        if ($warehouseId) {

            $wh = WarehouseItem::firstOrNew([
                     'warehouse_id'       => $warehouseId,
                     'product_subcard_id' => $productId,
                     'unit_measurement'   => $unitId,
                 ]);

            // если запись новая – начнём с нулей
            $wh->quantity   = ($wh->exists ? $wh->quantity   : 0) + $qty;
            $wh->brutto     = ($wh->exists ? $wh->brutto     : 0) + ($p['brutto']    ?? 0);
            $wh->netto      = ($wh->exists ? $wh->netto      : 0) + ($p['netto']     ?? 0);
            $wh->total_sum  = ($wh->exists ? $wh->total_sum  : 0) + ($p['total_sum'] ?? 0);

            $wh->price      = $p['price']       ?? 0;
            $wh->cost_price = $p['cost_price']  ?? 0;

            $wh->save();
        }
    }
}


private function syncExpenses(Document $doc, array $expenses): void
{
    foreach ($expenses as $e) {
        $expenseId  = data_get($e, 'name.id');
        $providerId = data_get($e, 'provider.id');
        $amount     = $e['amount'] ?? 0;

        $base = ReferenceItem::find($expenseId);
        if (!$base) continue;

        if ($base->provider_id == $providerId) {
            $base->update(['value' => $amount]);
            $refId = $base->id;
        } else {
            $clone = ReferenceItem::firstOrCreate(
                ['reference_id'=>$base->reference_id,'name'=>$base->name,'provider_id'=>$providerId],
                ['description'=>$base->description,'type'=>$base->type,'country'=>$base->country,'value'=>$amount]
            );
            if (!$clone->wasRecentlyCreated) $clone->update(['value'=>$amount]);
            $refId = $clone->id;
        }

        $doc->expenses()->create([
            'reference_item_id' => $refId,
            'provider_id'       => $providerId,
        ]);
    }
}
// update приход товара

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

