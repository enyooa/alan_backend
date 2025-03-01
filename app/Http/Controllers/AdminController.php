<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdminCashes;
use App\Models\BasicProductsPrice;
use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\PriceRequest;
use App\Models\Product_Group;
use App\Models\AdminWarehouse;
use App\Models\AdminWarehouseExpense;
use App\Models\Expense;
use App\Models\GeneralWarehouse;
use Illuminate\Support\Facades\Log;

use App\Models\Product;
use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\User;
use BeyondCode\LaravelWebSockets\Server\Loggers\Logger;
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
     * Create a single warehouse entry
     */
    public function createWarehouse(Request $request)
    {
        try {
            Log::info('Request Data:', $request->all());

            // Validate
            $validated = $request->validate([
                'organization_id' => 'nullable|integer|exists:users,id',
                'product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
                'unit_measurement' => 'nullable|string|max:255',
                'quantity' => 'nullable|numeric|min:0',
                'price' => 'nullable|numeric|min:0',
                'total_sum' => 'nullable|numeric|min:0',
                'date' => 'nullable|date_format:Y-m-d',
            ]);

            $adminWarehouse = AdminWarehouse::create($validated);

            return response()->json([
                'message' => 'Product received successfully',
                'data' => $adminWarehouse,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing product receiving:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to store product receiving data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk store warehouse entries
     * (Keeping the HEAD version: using "products" array)
     */
 
    public function receivingBulkStore(Request $request)
    {
        // 1) Включаем логирование всех запросов
        DB::listen(function ($query) {
            // Запишем SQL и привязки в лог-файл laravel.log
            Log::debug('SQL: '.$query->sql, $query->bindings);
        });

        try {
            Log::info('Received bulk receiving data:', $request->all());

            // 2) Валидация (примерная)
            $validated = $request->validate([
                'products' => 'required|array',

                'products.*.provider_id'        => 'integer|nullable',
                'products.*.product_subcard_id' => 'required|integer',
                'products.*.unit_measurement'   => 'nullable|string|max:255',
                'products.*.quantity'           => 'nullable|numeric|min:0',
                'products.*.brutto'             => 'nullable|numeric|min:0',
                'products.*.netto'              => 'nullable|numeric|min:0',
                'products.*.price'              => 'nullable|numeric|min:0',
                'products.*.total_sum'          => 'nullable|numeric|min:0',
                'products.*.date'               => 'nullable|date_format:Y-m-d',
                'products.*.additional_expense' => 'nullable|numeric|min:0',
                'products.*.cost_price'         => 'nullable|numeric|min:0',

                // Если "expenses" => массив объектов { expense_id:1 }, etc.
                'products.*.expenses' => 'array',
                'products.*.expenses.*.expense_id' => 'integer|exists:expenses,id',
            ]);

            foreach ($validated['products'] as $pData) {
                // 3) Создаём запись в admin_warehouses
                $adminWarehouse = AdminWarehouse::create([
                    'organization_id'     => $pData['provider_id'] ?? null,
                    'product_subcard_id'  => $pData['product_subcard_id'],
                    'unit_measurement'    => $pData['unit_measurement'] ?? null,
                    'quantity'            => $pData['quantity'] ?? 0,
                    'brutto'              => $pData['brutto'] ?? 0,
                    'netto'               => $pData['netto'] ?? 0,
                    'price'               => $pData['price'] ?? 0,
                    'total_sum'           => $pData['total_sum'] ?? 0,
                    'date'                => $pData['date'] ?? null,
                    'additional_expenses' => $pData['additional_expense'] ?? 0,
                    'cost_price'          => $pData['cost_price'] ?? 0,
                ]);

                // 4) Привязка расходов через pivot
                //    Предположим, мы просто храним список expense_id
                if (!empty($pData['expenses'])) {
                    // Собираем массив ID
                    $expenseIds = collect($pData['expenses'])
                        ->pluck('expense_id')
                        ->filter() // убрать null/0
                        ->toArray();

                    Log::debug("Attach expense IDs for AdminWarehouse#{$adminWarehouse->id}", $expenseIds);

                    // Выполняем attach
                    $adminWarehouse->expenses()->attach($expenseIds);
                }
            }

            // 5) Ответ об успехе
            return response()->json([
                'message' => 'Bulk product receiving with pivot expenses successfully stored!',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving product receiving:', ['error' => $e->getMessage()]);
            return response()->json([
                'error'   => 'Failed to store product receiving data',
                'message' => $e->getMessage(),
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

        $sales = DB::table('sales')
            ->select(
                'id',
                DB::raw('CAST(amount AS CHAR) as operation'),
                'created_at',
                DB::raw("'Продажа' as type")
            )
            ->get();

        $priceRequests = DB::table('price_requests')
            ->select(
                'id',
                DB::raw('CAST(amount AS CHAR) as operation'),
                'created_at',
                DB::raw("'Ценовое предложение' as type")
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

        $adminWarehouses = DB::table('admin_warehouses')
            ->select(
                'id',
                DB::raw('CAST(quantity AS CHAR) as operation'),
                'created_at',
                DB::raw("'Перемещение в склад' as type")
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

        // Combine and sort operations by creation date
        $operations = $productCards
            ->concat($productSubcards)
            ->concat($sales)
            ->concat($priceRequests)
            ->concat($unitMeasurements)
            ->concat($roles)
            ->concat($addresses)
            ->concat($adminWarehouses)
            ->concat($providers)
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

