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
    /**
     * Get all items in the user's basket.
     */
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

    /**
     * Add a product to the basket (HEAD version).
     */
    // app/Http/Controllers/BasketController.php

public function add(Request $request)
{
    try {
        $validated = $request->validate([
            'product_subcard_id' => 'required|integer',
            'source_table'       => 'required|string|in:sales,price_offer_items,favorites',
            'source_table_id'    => 'required|integer',
            'quantity'           => 'required|integer',
            'price'              => 'required|numeric|min:0',
            // Add new validations:
            'unit_measurement'   => 'required|string',
            'totalsum'           => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();

        // Look for existing Basket item with the same subcard + source_table + source_table_id
        $basketItem = Basket::where([
            'id_client_request'  => $userId,
            'product_subcard_id' => $validated['product_subcard_id'],
            'source_table'       => $validated['source_table'],
            'source_table_id'    => $validated['source_table_id'],
        ])->first();

        if ($basketItem) {
            // Update the quantity and price
            $basketItem->quantity += $validated['quantity'];
            $basketItem->price = $validated['price'];

            // Update the newly added fields:
            $basketItem->unit_measurement = $validated['unit_measurement'];
            $basketItem->totalsum = $validated['totalsum'];

            // If quantity <= 0, remove item
            if ($basketItem->quantity <= 0) {
                $basketItem->delete();
            } else {
                $basketItem->save();
            }
        }
        else {
            // Only create a new item if quantity > 0
            if ($validated['quantity'] > 0) {
                Basket::create([
                    'id_client_request'  => $userId,
                    'product_subcard_id' => $validated['product_subcard_id'],
                    'source_table'       => $validated['source_table'],
                    'source_table_id'    => $validated['source_table_id'],
                    'quantity'           => $validated['quantity'],
                    'price'              => $validated['price'],
                    // The new fields:
                    'unit_measurement'   => $validated['unit_measurement'],
                    'totalsum'           => $validated['totalsum'],
                ]);
            }
        }

        return response()->json(['success' => true], 201);

    } catch (\Exception $e) {
        Log::error("Basket add error: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 400);
    }
}


    /**
     * Remove a product from the basket.
     */
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

        return response()->json([
            'success' => true,
            'message' => 'Product removed from basket'
        ], 200);
    }

    /**
     * Clear all items from the user's basket.
     */
    public function clear()
    {
        $userId = Auth::id();
        Basket::where('id_client_request', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Basket cleared'
        ], 200);
    }

    /**
     * Place an order from the basket.
     */
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

    // Instead of 'status' => 'pending', use 'status_id' => 1
    $order = Order::create([
        'user_id'    => $user->id,
        'status_id'  => 1,         // 1 => "ожидание" in status_docs
        'address'    => $address,
    ]);

    foreach ($basketItems as $item) {
        OrderItem::create([
            'order_id'           => $order->id,
            'product_subcard_id' => $item->product_subcard_id,
            'source_table'       => $item->source_table,
            'source_table_id'    => $item->source_table_id,
            'quantity'           => $item->quantity,
            'price'              => $item->price,
            'unit_measurement'   => $item->unit_measurement,  // new
            'totalsum'           => $item->price * $item->quantity,          // new
        ]);
    }


    Basket::where('id_client_request', $user->id)->delete();

    return response()->json([
        'success' => true,
        'order'   => $order
    ]);
}

    /**
     * Get the details of an order, including product subcards.
     */
    public function getOrderDetails($orderId)
    {
        $order = Order::with('orderItems.productSubCard.productCard')->findOrFail($orderId);
        return response()->json(['success' => true, 'data' => $order]);
    }

    /**
     * Update the status of an order.
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status'  => 'required|in:pending,processing,packed,shipped,delivered,canceled',
            'remarks' => 'nullable|string',
        ]);

        $order = Order::findOrFail($orderId);
        $order->status = $validated['status'];

        // Update timestamps based on status
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
            'status'   => $validated['status'],
            'remarks'  => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'order'   => $order
        ]);
    }
}
