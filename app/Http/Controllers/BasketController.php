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
    public function index()
{
    $userId = Auth::id();

    $basketItems = Basket::where('id_client_request', $userId)->get();

    if ($basketItems->isEmpty()) {
        return response()->json(['basket' => []], 200);
    }

    return response()->json(['basket' => $basketItems], 200);
}


    /**
     * добавить продукт в корзину
     */
    public function add(Request $request)
{
    Log::info('Basket Add Request:', $request->all());
    try {
        $validated = $request->validate([
            'product_subcard_id' => 'required|exists:product_sub_cards,id',
            'source_table' => 'required|in:sales,price_requests',
            'quantity' => 'required|integer|min:1',
        ]);

        $basketItem = Basket::updateOrCreate(
            [
                'id_client_request' => Auth::id(),
                'product_subcard_id' => $validated['product_subcard_id'],
                'source_table' => $validated['source_table'],
            ],
            [
                'quantity' => DB::raw("quantity + {$validated['quantity']}"),
            ]
        );

        Log::info('Basket Add Success:', $basketItem->toArray());
        return response()->json(['success' => true, 'basket' => $basketItem], 201);

    } catch (\Exception $e) {
        Log::error('Basket Add Error:', ['message' => $e->getMessage()]);
        return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
    }
}


    /**
     * удалить с корзины один продукт
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

        return response()->json(['success' => true, 'message' => 'Product removed from basket'], 200);
    }

    /**
     * Очистить корзину
     */
    public function clear()
    {
        $userId = Auth::id();

        Basket::where('id_client_request', $userId)->delete();

        return response()->json(['success' => true, 'message' => 'Basket cleared'], 200);
    }

    public function placeOrder(Request $request)
{
    // Get the authenticated user
    $user = Auth::user();

    // Retrieve the user's default address or the first associated address
    $address = $user->addresses()->first();

    if (!$address) {
        return response()->json(['error' => 'User has no associated address'], 400);
    }

    // Validate other fields if necessary
    $validated = $request->validate([]);

    // Retrieve basket items for the authenticated user
    $basketItems = Basket::where('id_client_request', $user->id)->get();

    if ($basketItems->isEmpty()) {
        return response()->json(['error' => 'Your basket is empty'], 400);
    }

    // Create the order
    $order = Order::create([
        'packer_id' => 4, // Example: static packer ID; replace with actual logic if dynamic
        'user_id' => $user->id,
        'status' => 'pending',
        'address' => $address->name, // Use the address from the user
    ]);

    // Move items from the basket to order_items
    foreach ($basketItems as $item) {
        $price = null;

        // Fetch price from the appropriate source table
        if ($item->source_table === 'sales') {
            $price = DB::table('sales')->where('id', $item->product_subcard_id)->value('price');
        } elseif ($item->source_table === 'price_requests') {
            $price = DB::table('price_requests')->where('id', $item->product_subcard_id)->value('price');
        }

        OrderItem::create([
            'order_id' => $order->id,
            'product_subcard_id' => $item->product_subcard_id,
            'source_table' => $item->source_table,
            'quantity' => $item->quantity,
            'price' => $price,
        ]);
    }

    // Clear the user's basket after moving items to the order
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
    $order = Order::with('orderProducts.productSubCard.productCard')->findOrFail($orderId);

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
