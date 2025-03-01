<?php

namespace App\Http\Controllers;

use App\Models\CourierDocument;
use App\Models\CourierDocumentProduct;
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

    // Fetch orders assigned to the courier
    $orders = Order::with(['orderProducts.productSubCard.productCard'])
        ->where('courier_id', $courierId)
        ->whereNull('courier_document_id') // Ensure the order itself is not yet documented
        ->whereHas('orderProducts', function ($query) {
            $query->whereNull('courier_document_id') // Not yet documented
                  ->whereNotNull('packer_document_id'); // Packed items only
        })
        ->orderBy('created_at', 'desc') // Optional: order by creation date
        ->get();

    return response()->json([
        'success' => true,
        'orders' => $orders,
    ]);
}

    
public function getCourierOrderDetails(Request $request, $orderId)
{
    // Log::info('Fetching details for order ID: ' . $orderId . ' by user ID: ' . $request->user()->id);
    $courierId = Auth::id(); // Get the authenticated courier's ID

    $order = Order::where('id', $orderId)
        ->where('courier_id', $courierId) // Ensure the order is assigned to the courier
        ->with([
            'orderProducts.productSubCard.productCard', // Include product details
            'orderProducts.source', // Include the source relationship if needed
        ])
        ->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found or not authorized to view',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $order,
    ]);
}


    /**
     * Submit delivery for a specific order.
     */
    public function submitCourierDelivery(Request $request, $orderId)
    {
        $validatedData = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:order_items,id',
            'products.*.quantity' => 'required|numeric|min:1',
        ]);

        $order = Order::find($orderId);

        if (!$order || $order->courier_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or unauthorized access.',
            ], 404);
        }

        // Update the order products with the delivered quantities
        foreach ($validatedData['products'] as $product) {
            $orderProduct = $order->orderProducts()->find($product['id']);
            if ($orderProduct) {
                $orderProduct->update([
                    'quantity' => $product['quantity'],
                    'courier_document_id' => $request->user()->id, // Mark as delivered by the courier
                ]);
            }
        }

        // Mark the order as delivered
        $order->update([
            'courier_document_id' => $request->user()->id,
            'status' => 'delivered',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery submitted successfully.',
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
    Log::info($request->all()); // Log incoming request for debugging

    // Validate the incoming request
    $validatedData = $request->validate([
        'order_id' => 'required|exists:orders,id',
        'order_products' => 'required|array|min:1',
        'order_products.*.product_subcard_id' => 'required|exists:product_sub_cards,id',
        'order_products.*.quantity' => 'required|integer|min:1',
        'order_products.*.price' => 'required|numeric|min:0',
    ]);

    try {
        // Step 1: Create a new courier document
        $courierDocument = CourierDocument::create([
            'courier_id' => Auth::id(), // Assuming the authenticated user is the courier
            'amount_of_products' => count($validatedData['order_products']),
            'is_confirmed' => false,
        ]);

        // Step 2: Attach products to the courier document
        foreach ($validatedData['order_products'] as $product) {
            $courierDocument->documentProducts()->create([
                'product_subcard_id' => $product['product_subcard_id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'source_table_id' => $product['source_table_id'], // Optional, depends on your requirements
            ]);
        }

        // Step 3: Update the order with the courier document ID
        $order = Order::findOrFail($validatedData['order_id']);
        $order->update(['courier_document_id' => $courierDocument->id]);

        return response()->json([
            'message' => 'Courier document created and order updated successfully.',
            'courier_document_id' => $courierDocument->id,
        ], 201);
    } catch (\Exception $e) {
        Log::error('Error storing courier document: ' . $e->getMessage());
        return response()->json(['error' => 'Operation failed: ' . $e->getMessage()], 500);
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
