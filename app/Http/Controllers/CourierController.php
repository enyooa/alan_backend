<?php

namespace App\Http\Controllers;

use App\Models\CourierDocument;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackerDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierController extends Controller
{
    public function getCourierOrders(Request $request)
{
    $courierId = $request->user()->id;

    $orders = Order::with(['orderProducts' => function ($query) {
            $query->whereNull('courier_document_id')
                  ->with('productSubCard.productCard');
        }])
        ->where('courier_id', $courierId)
        ->whereHas('orderProducts', function ($query) {
            $query->whereNull('courier_document_id');
        })
        ->get();

    return response()->json([
        'success' => true,
        'orders' => $orders,
    ]);
}


public function getCourierUsers()
{
    try {
        $couriers = User::whereHas('roles', function ($query) {
            $query->where('name', 'courier');
        })
        ->with('addresses:name') // Load addresses and select only the name
        ->get(['id', 'first_name', 'last_name', 'whatsapp_number']);

        return response()->json($couriers, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch couriers', 'message' => $e->getMessage()], 500);
    }
}
public function storeCourierDocument(Request $request)
{
    Log::info('Received Courier Document Request:', $request->all());

    $validatedData = $request->validate([
        'orders' => 'required|array',
        'orders.*.order_id' => 'required|integer|exists:orders,id',
        'orders.*.order_products' => 'required|array',
        'orders.*.order_products.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
        'orders.*.order_products.*.quantity' => 'required|integer|min:1',
    ]);

    DB::beginTransaction();

    try {
        // Create the CourierDocument
        $courierDocument = CourierDocument::create([
            'amount_of_products' => count($validatedData['orders']),
            'is_confirmed' => false,
        ]);

        foreach ($validatedData['orders'] as $order) {
            foreach ($order['order_products'] as $product) {
                $orderItem = OrderItem::where([
                    'order_id' => $order['order_id'],
                    'product_subcard_id' => $product['product_subcard_id'],
                ])->first();

                if ($orderItem) {
                    $orderItem->update(['courier_document_id' => $courierDocument->id]);
                } else {
                    Log::warning('OrderItem not found for order_id=' . $order['order_id'] .
                        ' and product_subcard_id=' . $product['product_subcard_id']);
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Courier document created successfully.',
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating courier document:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function confirmCourierDocument(Request $request)
{
    // Log the incoming request body
    Log::info('Request payload:', $request->all());

    // Validate JSON structure
    $validated = $request->validate([
        'courier_document_id' => 'required|exists:courier_documents,id',
    ]);

    $courierDocument = CourierDocument::find($validated['courier_document_id']);

    if (!$courierDocument) {
        return response()->json(['error' => 'Courier document not found.'], 404);
    }

    if ($courierDocument->is_confirmed) {
        return response()->json(['error' => 'Документ подтвержден.'], 400);
    }

    $courierDocument->is_confirmed = true;
    $courierDocument->save();

    return response()->json(['message' => 'Courier document confirmed successfully.']);
}


}
