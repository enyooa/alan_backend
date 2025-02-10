<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\PriceOffer;
use App\Models\PriceOfferOrder;
use App\Models\ProductSubCard;
use App\Models\Unit_measurement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PriceOfferController extends Controller
{
    public function fetch_data_of_price_offer() {
    try {
        // Retrieve client users
        $clientUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->with('addresses')->get(['id', 'first_name', 'last_name', 'whatsapp_number']);

        // Retrieve subcards with remaining quantities
        $subCards = ProductSubCard::all()->map(function ($subCard) {
            $remainingQuantity = AdminWarehouse::where('product_subcard_id', $subCard->id)->sum('quantity');
            return array_merge($subCard->toArray(), ['remaining_quantity' => $remainingQuantity]);
        });

        // Retrieve units of measurement
        $units = Unit_measurement::select('name')->distinct()->get();

        // Prepare the response
        $response = [
            'client_users' => $clientUsers,
            'subcards' => $subCards,
            'units' => $units,
        ];

        return response()->json($response, 200);

    } catch (\Exception $e) {
        Log::error('Error fetching price offer data', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to fetch data', 'message' => $e->getMessage()], 500);
    }
}
public function bulkPriceOffers(Request $request)
{
    Log::info($request->all());

    // Validate the incoming request data
    $validatedData = $request->validate([
        'client_id' => 'required|integer',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'price_offers' => 'required|array',
        'price_offers.*.product_subcard_id' => 'required|integer',
        'price_offers.*.unit_measurement' => 'nullable|string',
        'price_offers.*.amount' => 'required|integer',
        'price_offers.*.price' => 'nullable|numeric',
        'price_offers.*.totalsum' => 'nullable|numeric',
        'price_offers.*.start_date' => 'nullable|date',
        'price_offers.*.end_date' => 'nullable|date',
        'price_offers.*.choice_status' => 'nullable|string',
        'price_offers.*.address_id' => 'required|integer', // Moved address_id here
    ]);

    DB::transaction(function () use ($validatedData) {
        // Calculate the total sum of all price offers
        $totalSum = array_sum(array_column($validatedData['price_offers'], 'totalsum'));

        // Create the PriceOfferOrder
        $priceOfferOrder = PriceOfferOrder::create([
            'client_id' => $validatedData['client_id'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'totalsum' => $totalSum,
        ]);

        // Prepare PriceOffer items
        $priceOffers = array_map(function ($offer) use ($priceOfferOrder) {
            return new PriceOffer(array_merge($offer, [
                'price_offer_order_id' => $priceOfferOrder->id,
            ]));
        }, $validatedData['price_offers']);

        // Save all PriceOffer items
        $priceOfferOrder->priceOffers()->saveMany($priceOffers);
    });

    return response()->json(['message' => 'Price offers created successfully.'], 201);
}


}
