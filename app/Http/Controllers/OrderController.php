<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function assignPacker(Request $request, $orderId)
    {
        $validated = $request->validate([
            'packer_id' => 'required|exists:users,id',
        ]);
    
        $order = Order::findOrFail($orderId);
        $order->packer_id = $validated['packer_id'];
        $order->status = 'assigned_to_packer'; // Update the status
        $order->save();
    
        return response()->json(['success' => true, 'order' => $order]);
    }

    public function getPackerOrders()
{
    // Fetch orders where packer_document_id is NULL
    $orders = Order::with(['orderProducts.productSubCard.productCard'])
        ->whereNull('packer_document_id') // Filter on orders table
        ->get();

    return response()->json(['success' => true, 'orders' => $orders]);
}

public function getHistoryOrders()
{
    // Fetch orders where packer_document_id is not null
    $orders = Order::with(['orderProducts.productSubCard.productCard'])
        ->whereNotNull('packer_document_id') // Filter at the Order level
        ->get();

    return response()->json(['success' => true, 'orders' => $orders]);
}





    

public function getDetailedOrder($orderId)
{
    $packerId = Auth::id();

    $order = Order::where('id', $orderId)
        ->where('packer_id', $packerId)
        ->with([
            'orderProducts.productSubCard.productCard',
            'orderProducts.source', // Include the source relationship
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


// накладные фасовщика
public function updateOrderProducts(Request $request, $orderId)
{
    $validated = $request->validate([
        'products' => 'required|array',
        'products.*.id' => 'required|integer|exists:products,id',
        'products.*.amount' => 'required|numeric|min:0',
    ]);

    $order = Order::findOrFail($orderId);

    foreach ($validated['products'] as $product) {
        $orderProduct = $order->products()->where('id', $product['id'])->first();
        if ($orderProduct) {
            $orderProduct->pivot->amount = $product['amount'];
            $orderProduct->pivot->save();
        }
    }

    return response()->json(['success' => true, 'message' => 'Order updated successfully.']);
}


public function getInvoice()
{
    try {
        $documents = PackerDocument::all();

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch documents.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Создать накладную фасовщика
 */
public function storeInvoice(Request $request)
{
    $validated = $request->validate([
        'requests' => 'required|array',
        'requests.*.id_courier' => 'required|integer',
        'requests.*.delivery_address' => 'nullable|string|max:255',
        'requests.*.product_subcard_id' => 'required|integer|exists:product_subcards,id',
        'requests.*.amount_of_products' => 'required|numeric|min:0',
    ]);

    try {
        foreach ($validated['requests'] as $requestData) {
            PackerDocument::create($requestData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documents saved successfully.',
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to save documents.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
