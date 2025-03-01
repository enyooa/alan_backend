<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\PriceOffer;
use App\Models\PriceOfferOrder;
use App\Models\ProductSubCard;
use App\Models\Unit_measurement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

// меняем формат vue js чтобы создать ценовое предложение
protected function normalizeField(array $data, string $fieldName)
{
    if (isset($data[$fieldName]) && is_array($data[$fieldName]) && isset($data[$fieldName]['value'])) {
        // If the field is nested, extract the value.
        return $data[$fieldName]['value'];
    }
    return $data[$fieldName] ?? null;
}

protected function normalizePriceOffers(array $priceOffers)
{
    return array_map(function ($offer) {
        // Normalize individual fields if needed.
        foreach (['product_subcard_id', 'unit_measurement', 'amount', 'price', 'batch_id'] as $field) {
            if (isset($offer[$field]) && is_array($offer[$field]) && isset($offer[$field]['value'])) {
                $offer[$field] = $offer[$field]['value'];
            }
        }
        return $offer;
    }, $priceOffers);
}

public function bulkPriceOffers(Request $request)
{
    // Get the raw input data
    $data = $request->all();
    
    // Normalize top-level fields
    $data['client_id']   = $this->normalizeField($data, 'client_id');
    $data['start_date']  = $this->normalizeField($data, 'start_date');
    $data['end_date']    = $this->normalizeField($data, 'end_date');
    
    // For address_id, if it's nested and includes an object, extract the id
    $address = $this->normalizeField($data, 'address_id');
    if (is_array($address) && isset($address['id'])) {
        $data['address_id'] = $address['id'];
    } else {
        $data['address_id'] = $address;
    }
    
    // Normalize the price_offers array
    if (isset($data['price_offers']) && is_array($data['price_offers'])) {
        $data['price_offers'] = $this->normalizePriceOffers($data['price_offers']);
    }
    
    Log::info($data);
    
    // Now validate the normalized data
    $validatedData = validator($data, [
        'client_id' => 'required|integer',
        'address_id' => 'required|integer|exists:addresses,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'price_offers' => 'required|array',
        'price_offers.*.product_subcard_id' => 'required|integer',
        'price_offers.*.unit_measurement' => 'nullable|string',
        'price_offers.*.amount' => 'required|numeric|min:0',
        'price_offers.*.price' => 'required|numeric|min:0',
        // 'price_offers.*.batch_id' can be optional if you need it.
    ])->validate();
    
    DB::beginTransaction();
    try {
        // Calculate the overall total sum (each line: amount * price)
        $totalSum = 0;
        foreach ($validatedData['price_offers'] as $offer) {
            $totalSum += $offer['amount'] * $offer['price'];
        }
    
        // Create the PriceOfferOrder record (the "header" for the offer)
        $priceOfferOrder = PriceOfferOrder::create([
            'client_id' => $validatedData['client_id'],
            'address_id' => $validatedData['address_id'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'totalsum' => $totalSum,
        ]);
    
        // Prepare PriceOffer items (the individual products within the offer)
        $priceOffers = [];
        foreach ($validatedData['price_offers'] as $offer) {
            $offer['totalsum'] = $offer['amount'] * $offer['price'];
            $offer['price_offer_order_id'] = $priceOfferOrder->id;
            $priceOffers[] = new PriceOffer($offer);
        }
    
        // Attach the PriceOffer items to the PriceOfferOrder
        $priceOfferOrder->priceOffers()->saveMany($priceOffers);
    
        DB::commit();
    
        return response()->json([
            'message' => 'Price offer created successfully.',
            'price_offer_order' => $priceOfferOrder->load('priceOffers'),
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating price offer: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating price offer.'], 500);
    }
}


// клиент получает ценовые предложения
public function getUserPriceOffers(Request $request)
{
    try {
        // 1) Determine the client/user ID (e.g. from request or auth user)
        $clientId = Auth::user()->id;
        // Or, if you want to use the authenticated user: 
        // $clientId = auth()->id();

        // 2) Fetch all price offer orders for this client
        //    Eager-load the 'priceOffers' relationship,
        //    and also load each priceOffer's related 'productSubCard'
        $priceOfferOrders = PriceOfferOrder::where('client_id', $clientId)
            ->with(['priceOffers.productSubCard'])
            ->get();

        // 3) Return JSON response
        return response()->json([
            'success' => true,
            'data' => $priceOfferOrders,
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error fetching user price offers:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch price offers for user.',
        ], 500);
    }
}

}
