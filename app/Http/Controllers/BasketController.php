<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Basket;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BasketController extends Controller
{
    // Get all items in the basket
    public function index()
    {
        $userId = Auth::id();

        $basketItems = Basket::where('id_client_request', $userId)
            ->with(['productSubCard.productCard']) // Include related product details
            ->get();

        if ($basketItems->isEmpty()) {
            return response()->json(['basket' => []], 200);
        }

        $basketData = $basketItems->map(function ($item) {
            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'price' => $item->price ?? 0, // Default price to 0 if null
                'product_subcard_id' => $item->product_subcard_id,
                'source_table' => $item->source_table,
                'delivery_date' => $item->delivery_date,
                'product_details' => [
                    'subcard_name' => $item->productSubCard->name ?? null,
                    'brutto' => $item->productSubCard->brutto ?? null,
                    'netto' => $item->productSubCard->netto ?? null,
                    'product_card' => [
                        'name_of_products' => $item->productSubCard->productCard->name_of_products ?? null,
                        'description' => $item->productSubCard->productCard->description ?? null,
                        'photo_product' => $item->productSubCard->productCard->photo_product ?? null,
                    ],
                ],
            ];
        });
        

        return response()->json(['basket' => $basketData], 200);
    }

    // Add a product to the basket
    public function add(Request $request)
{
    try {
        $validated = $request->validate([
            'product_subcard_id' => 'required|exists:product_sub_cards,id',
            'source_table' => 'required|in:sales,price_requests,favorites',
            'source_table_id' => 'required|integer',
            'quantity' => 'required|integer', // Allow negative values for decrement
            'price' => 'required|numeric|min:0', // Ensure price is provided
        ]);

        $userId = Auth::id();

        // Check if the item already exists in the basket
        $basketItem = Basket::where([
            'id_client_request' => $userId,
            'product_subcard_id' => $validated['product_subcard_id'],
            'source_table' => $validated['source_table'],
            'source_table_id' => $validated['source_table_id'],
        ])->first();

        if ($basketItem) {
            $basketItem->quantity += $validated['quantity'];

            if ($basketItem->quantity <= 0) {
                $basketItem->delete(); // Remove if quantity is 0 or less
            } else {
                $basketItem->price = $validated['price'];
                $basketItem->save();
            }
        } elseif ($validated['quantity'] > 0) {
            Basket::create([
                'id_client_request' => $userId,
                'product_subcard_id' => $validated['product_subcard_id'],
                'source_table' => $validated['source_table'],
                'source_table_id' => $validated['source_table_id'],
                'quantity' => $validated['quantity'],
                'price' => $validated['price'],
            ]);
        }

        return response()->json(['success' => true], 201);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

    


    // Remove a product from the basket
    public function remove(Request $request)
    {
        $validated = $request->validate([
            'product_subcard_id' => 'required|exists:baskets,product_subcard_id',
        ]);

        $userId = Auth::id();

        $basketItem = Basket::where('id_client_request', $userId)
            ->where('product_subcard_id', $validated['product_subcard_id'])
            ->first();

        if (!$basketItem) {
            return response()->json(['error' => 'Product not found in basket'], 404);
        }

        $basketItem->delete();

        return response()->json(['success' => true, 'message' => 'Product removed from basket'], 200);
    }

    // Clear the basket
    public function clear()
    {
        $userId = Auth::id();

        Basket::where('id_client_request', $userId)->delete();

        return response()->json(['success' => true, 'message' => 'Basket cleared'], 200);
    }

    // Place an order from the basket
    public function placeOrder(Request $request)
    {
        Log::info($request->all());
        $user = Auth::user();
        $address = $request->address;

        if (!$address) {
            return response()->json(['error' => 'User has no associated address'], 400);
        }

        $basketItems = Basket::where('id_client_request', $user->id)->get();

        if ($basketItems->isEmpty()) {
            return response()->json(['error' => 'Your basket is empty'], 400);
        }

        $order = Order::create([
            'packer_id' => 4,
            'user_id' => $user->id,
            'status' => 'pending',
            'address' => $address,
        ]);

        foreach ($basketItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_subcard_id' => $item->product_subcard_id,
                'source_table' => $item->source_table,
                'source_table_id' => $item->source_table_id,
                'quantity' => $item->quantity,
                'price' => $item->price, // Use price directly from basket
            ]);
        }

        Basket::where('id_client_request', $user->id)->delete();

        return response()->json(['success' => true, 'order' => $order]);
    }


// public function getOrderDetails($orderId)
// {
//     $order = Order::with('orderProducts')->findOrFail($orderId);

//     $orderItems = $order->orderItems->map(function ($item) {
//         $productDetails = null;

//         // Fetch product details from the source table
//         if ($item->source_table === 'sales') {
//             $productDetails = DB::table('sales')->where('id', $item->product_subcard_id)->first();
//         } elseif ($item->source_table === 'price_requests') {
//             $productDetails = DB::table('price_requests')->where('id', $item->product_subcard_id)->first();
//         }

//         return [
//             'product' => $productDetails,
//             'quantity' => $item->quantity,
//             'price' => $item->price,
//         ];
//     });

//     return response()->json(['order' => $order, 'items' => $orderItems]);
// }
public function getOrderDetails($orderId)
{
    $order = Order::with('orderItems.productSubCard.productCard')->findOrFail($orderId);

    return response()->json(['success' => true, 'data' => $order]);
}

public function updateOrderStatus(Request $request, $orderId)
{
    $validated = $request->validate([
        'status' => 'required|in:pending,processing,packed,shipped,delivered,canceled', // Allowed statuses
        'remarks' => 'nullable|string', // Optional remarks for logging
    ]);

    $order = Order::findOrFail($orderId);
    $order->status = $validated['status'];

    // Update specific timestamps based on status
    if ($validated['status'] === 'shipped') {
        $order->shipped_at = now();
    }
    if ($validated['status'] === 'delivered') {
        $order->delivered_at = now();
    }

    $order->save();

    // Log the status change (optional)
    Order::create([
        'order_id' => $order->id,
        'status' => $validated['status'],
        'remarks' => $validated['remarks'] ?? null,
    ]);

    return response()->json(['success' => true, 'message' => 'Order status updated', 'order' => $order]);
}



}
