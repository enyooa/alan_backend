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
use Illuminate\Support\Facades\Log;

use App\Models\Product;
use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(){

    }

    // public function create_user(Request $request){
    //     $user create([]);

    // }

    


    public function create_product(Request $request){
        return ProductCard::create($request->query->all());
    }



    public function storeProduct(Request $request)
{
    $request->validate([
        'name_of_products' => 'required|string',
        'type' => 'required|string',
        'photo_product' => 'nullable|image|mimes:jpeg,png,jpg,gif',
    ]);

    $data = $request->all();
    if ($request->hasFile('photo_product')) {
        // сохр в папку 'public/products/photos' 
        $imagePath = $request->file('photo_product')->store('products/photos', 'public');
        $data['photo_product'] = $imagePath;
    }

    ProductCard::create($data);

    return response()->json(['success' => 'Product created successfully.'], 201);
}


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
        // Store the new image
        $imagePath = $request->file('photo_product')->store('products/photos', 'public');
        $data['photo_product'] = $imagePath;

        // Optionally delete the old photo file if it exists
        if ($product->photo_product && Storage::disk('public')->exists($product->photo_product)) {
            Storage::disk('public')->delete($product->photo_product);
        }
    }

    $product->update($data);

    return response()->json(['success' => 'Product updated successfully.']);
}


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

    // Reduce the product quantity
    $product->quantity -= $request->quantity;
    $product->save();

    // Create a new sale record
    $sale = new ProductSubCard();
    $sale->product_id = $product->id;
    $sale->quantity_sold = $request->quantity;
    $sale->price_at_sale = $request->price;
    $sale->save();

    return response()->json(['message' => 'Product sold successfully', 'sale' => $sale]);
}

// Ценовое предложение

// Create a new offer request
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

// Get all offer requests
public function getOfferRequests()
{
    $requests = PriceRequest::with(['user', 'product'])->get();
    return response()->json($requests, 200);
}

// Get a specific offer request
public function getOfferRequest($id)
{
    $request = PriceRequest::with(['user', 'product'])->find($id);

    if (!$request) {
        return response()->json(['message' => 'Request not found'], 404);
    }

    return response()->json($request, 200);
}
// Ценовое предложение end

// ТМЗ

    public function createWarehouse(Request $request)
    {
        try {
            Log::info('Request Data:', $request->all());

            // Validate the incoming request
            $validated = $request->validate([
                'organization_id' => 'nullable|integer|exists:users,id',
                'product_card_id' => 'required|integer|exists:product_cards,id',
                'unit_measurement' => 'nullable|string|max:255',
                'quantity' => 'nullable|numeric|min:0',
                'price' => 'nullable|numeric|min:0',
                'total_sum' => 'nullable|numeric|min:0',
                'date' => 'nullable|date_format:Y-m-d', // Ensure the date format is correct
            ]);

            // Save the validated data to the database
            $adminWarehouse = AdminWarehouse::create($validated);

            // Return a success response
            return response()->json([
                'message' => 'Product received successfully',
                'data' => $adminWarehouse,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing product receiving:', ['error' => $e->getMessage()]);
            // Return a generic error response
            return response()->json([
                'error' => 'Failed to store product receiving data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // принятие товаров в склад админа
    public function receivingBulkStore(Request $request)
{
    try {
        // Log the incoming request for debugging
        Log::info('Received bulk receiving data:', $request->all());

        // Validate the incoming data
        $validated = $request->validate([
            'receivings' => 'required|array',
            'receivings.*.organization_id' => 'nullable|integer|exists:users,id',
            'receivings.*.product_card_id' => 'required|integer|exists:product_cards,id',
            'receivings.*.unit_measurement' => 'nullable|string|max:255',
            'receivings.*.quantity' => 'nullable|numeric|min:0',
            'receivings.*.price' => 'nullable|numeric|min:0',
            'receivings.*.total_sum' => 'nullable|numeric|min:0',
            'receivings.*.date' => 'nullable|date_format:Y-m-d',
        ]);

        // Insert each validated receiving into the database
        foreach ($validated['receivings'] as $receiving) {
            AdminWarehouse::create($receiving);
        }

        // Return a success response
        return response()->json([
            'message' => 'Bulk product receiving successfully stored!',
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Return validation error messages
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        // Log and return the error if insertion fails
        Log::error('Error saving product receiving:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Failed to store product receiving data',
            'message' => $e->getMessage(),
        ], 500);
    }
}


// Админ добавляет на свой склад карточку товара


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
        return response()->json(['message' => 'Unit deleted'], 200);
    }


    public function getClientUsers()
{
    try {
        $clientUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->get(['id', 'first_name', 'last_name', 'whatsapp_number']);

        return response()->json($clientUsers, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch client users', 'message' => $e->getMessage()], 500);
    }
}



    // справочник
    public function fetchOperationsHistory()
{
    // Fetch operations from various tables with localized names
    $productCards = DB::table('product_cards')
        ->select('id', 'name_of_products as operation', 'created_at', DB::raw("'Карточка товара' as type"))
        ->get();

    $productSubcards = DB::table('product_sub_cards')
        ->select('id', 'name as operation', 'created_at', DB::raw("'Подкарточка товара' as type"))
        ->get();

    $sales = DB::table('sales')
        ->select('id', 'amount as operation', 'created_at', DB::raw("'Продажа' as type"))
        ->get();

    $priceRequests = DB::table('price_requests')
        ->select('id', 'amount as operation', 'created_at', DB::raw("'Ценовое предложение' as type"))
        ->get();

    // Combine and sort operations by creation date
    $operations = $productCards
        ->concat($productSubcards)
        ->concat($sales)
        ->concat($priceRequests)
        ->sortByDesc('created_at')
        ->values();

    return response()->json($operations, 200);
}

public function updateOperation(Request $request, $id, $type)
{
    switch ($type) {
        case 'product_card':
            $operation = ProductCard::findOrFail($id);
            break;
        case 'product_subcard':
            $operation = ProductSubCard::findOrFail($id);
            break;
        case 'sale':
            $operation = Sale::findOrFail($id);
            break;
        case 'price_request':
            $operation = PriceRequest::findOrFail($id);
            break;
        default:
            return response()->json(['message' => 'Invalid operation type'], 400);
    }

    $operation->update($request->all());

    return response()->json(['message' => 'Operation updated successfully'], 200);
}


public function deleteOperation($id, $type)
{
    switch ($type) {
        case 'product_card':
            ProductCard::destroy($id);
            break;
        case 'product_subcard':
            ProductSubCard::destroy($id);
            break;
        case 'sale':
            Sale::destroy($id);
            break;
        case 'price_request':
            PriceRequest::destroy($id);
            break;
        default:
            return response()->json(['message' => 'Invalid operation type'], 400);
    }

    return response()->json(['message' => 'Operation deleted successfully'], 200);
}


public function adminCashes()
    {
        $adminCashes = AdminCashes::all(['id', 'name', 'IBAN']);
        return response()->json($adminCashes, 200);
    }


}
