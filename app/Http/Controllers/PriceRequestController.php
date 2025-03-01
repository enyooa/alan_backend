<?php

namespace App\Http\Controllers;

use App\Models\PriceOfferOrder;
use Illuminate\Http\Request;
use App\Models\PriceRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class PriceRequestController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Request data:', $request->all());

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
            'products.*.amount' => 'required|integer|min:1',
            'products.*.price' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            foreach ($request->products as $product) {
                PriceRequest::create([
                    'user_id' => $request->client_id,
                    'product_subcard_id' => $product['product_subcard_id'],
                    'amount' => $product['amount'],
                    'price' => $product['price'],
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);
            }

            return response()->json(['message' => 'Price offer stored successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to store price offer: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store multiple price offers in bulk.
     */
    public function bulkStore(Request $request)
    {
        Log::info($request->all());
    
        // Extract the data directly from the request
        $data = $request->all();
    
        // Ensure address_id exists in the first product
        if (empty($data['price_offers'][0]['address_id'])) {
            return response()->json(['error' => 'Missing address_id in request'], 400);
        }
    
        // Use the address_id from the first product
        $addressId = $data['price_offers'][0]['address_id'];
    
        // Create the PriceRequest
        $priceRequest = PriceRequest::create([
            'choice_status' => 'pending', // Default choice status
            'user_id' => $data['client_id'],
            'address_id' => $addressId,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);
    
        // Loop through products and create PriceOfferOrder for each
        foreach ($data['price_offers'] as $product) {
            PriceOfferOrder::create([
                'price_request_id' => $priceRequest->id, // Link to the request
                'product_subcard_id' => $product['product_subcard_id'],
                'unit_measurement' => $product['unit_measurement'],
                'amount' => $product['amount'],
                'price' => $product['price'] ?? 0, // Handle NULL price
                'total' => ($product['amount'] ?? 0) * ($product['price'] ?? 0), // Calculate total
            ]);
        }
    
        return response()->json(['success' => true, 'message' => 'Price offer saved successfully.']);
    }
    




public function getUserPriceRequests()
{
    // Ensure the user is authenticated
    $userId = Auth::id();

    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    // Fetch price requests for the authenticated user
    $priceRequests = PriceRequest::where('user_id', $userId)
        ->with([
            'productSubCard.productCard' // Eager load related data
        ])
        ->get();

    return response()->json([
        'success' => true,
        'data' => $priceRequests,
    ]);
}


public function update(Request $request, $id)
{
    $priceRequest = PriceRequest::findOrFail($id);

    $validated = $request->validate([
        'choice_status' => 'nullable|string',
        'user_id' => 'nullable|integer|exists:users,id',
        'address_id' => 'nullable|integer',
        'product_card_id' => 'nullable|integer|exists:product_cards,id',
        'unit_measurement' => 'nullable|string',
        'amount' => 'nullable|numeric',
        'price' => 'nullable|numeric',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date',
    ]);

    $priceRequest->update($validated);

    return response()->json(['message' => 'Price Request updated successfully'], 200);
}

public function destroy($id)
{
    PriceRequest::destroy($id);

    return response()->json(['message' => 'Price Request deleted successfully'], 200);
}


}