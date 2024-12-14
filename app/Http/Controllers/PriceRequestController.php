<?php

namespace App\Http\Controllers;

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
    try {
        // Log the entire incoming request for debugging
        Log::info('Incoming Request Payload:', $request->all());

        // Validate the incoming data
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'price_offers' => 'required|array',
            'price_offers.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
            'price_offers.*.unit_measurement' => 'required|string|max:255',
            'price_offers.*.amount' => 'required|numeric|min:0',
            'price_offers.*.price' => 'required|numeric|min:0',
            'price_offers.*.address_id' => 'required|integer|exists:addresses,id', // Validate address_id
        ]);

        // Log validated data for debugging
        Log::info('Validated Data:', $validated);

        // Prepare bulk insert data
        $bulkData = [];
        foreach ($validated['price_offers'] as $offer) {
            $bulkData[] = [
                'user_id' => $validated['client_id'],
                'address_id' => $offer['address_id'], // Include address_id
                'product_subcard_id' => $offer['product_subcard_id'],
                'unit_measurement' => $offer['unit_measurement'],
                'amount' => $offer['amount'],
                'price' => $offer['price'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert data into the database
        PriceRequest::insert($bulkData);

        // Return success response
        return response()->json([
            'message' => 'Price offers successfully stored!',
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Return validation errors
        Log::warning('Validation Error:', $e->errors());
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        // Log and return error if something goes wrong
        Log::error('Error storing bulk price offers:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Failed to store price offers',
            'message' => $e->getMessage(),
        ], 500);
    }
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

}