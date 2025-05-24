<?php

namespace App\Http\Controllers;

use App\Models\CourierDocument;
use App\Models\CourierDocumentProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackerDocument;
use App\Models\StatusDoc;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierController extends Controller
{
    public function getCourierOrders(Request $request)
{
    $orgId     = $request->user()->organization_id;   // ← your org
    $courierId = $request->user()->id;                // ← current courier

    $orders = Order::forOrg($orgId)                   // 👈 NEW SCOPE
        ->with([
            'orderProducts.productSubCard.productCard',
            'packer:id,first_name,last_name,photo',
            'courier:id,first_name,last_name,photo',
            'client:id,first_name,last_name',
            'statusDoc:id,name',
        ])
        ->whereNotNull('packer_id')
        // ->where('courier_id', $courierId)          // add if you only want “my” orders
        ->latest()
        ->get();

    $statuses = StatusDoc::select('id', 'name')->get();

    return response()->json([
        'success'  => true,
        'orders'   => $orders,
        'statuses' => $statuses,
    ]);
}





public function getCourierOrderDetails(Request $request, $orderId)
{
    $orgId = $request->user()->organization_id;

    $order = Order::forOrg($orgId)        // 👈 ensure same org
        ->where('id', $orderId)
        ->with([
            'orderProducts.productSubCard.productCard',
            'orderProducts.source',
        ])
        ->first();

    if (! $order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found or not authorized to view.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data'    => $order,
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





// public function storeCourierDocument(Request $request)
// {
//     Log::info($request->all()); // Log incoming request for debugging

//     // Validate the incoming request
//     $validatedData = $request->validate([
//         'order_id' => 'required|exists:orders,id',
//         'order_products' => 'required|array|min:1',
//         'order_products.*.product_subcard_id' => 'required|exists:product_sub_cards,id',
//         'order_products.*.quantity' => 'required|integer|min:1',
//         'order_products.*.price' => 'required|numeric|min:0',
//     ]);

//     try {
//         // Step 1: Create a new courier document
//         $courierDocument = CourierDocument::create([
//             'courier_id' => Auth::id(), // Assuming the authenticated user is the courier
//             'amount_of_products' => count($validatedData['order_products']),
//             'is_confirmed' => false,
//         ]);

//         // Step 2: Attach products to the courier document
//         foreach ($validatedData['order_products'] as $product) {
//             $courierDocument->documentProducts()->create([
//                 'product_subcard_id' => $product['product_subcard_id'],
//                 'quantity' => $product['quantity'],
//                 'price' => $product['price'],
//                 'source_table_id' => $product['source_table_id'], // Optional, depends on your requirements
//             ]);
//         }

//         // Step 3: Update the order with the courier document ID
//         $order = Order::findOrFail($validatedData['order_id']);
//         $order->update(['courier_document_id' => $courierDocument->id]);

//         return response()->json([
//             'message' => 'Courier document created and order updated successfully.',
//             'courier_document_id' => $courierDocument->id,
//         ], 201);
//     } catch (\Exception $e) {
//         Log::error('Error storing courier document: ' . $e->getMessage());
//         return response()->json(['error' => 'Operation failed: ' . $e->getMessage()], 500);
//     }
// }

public function storeCourierDocument(Request $request)
{
    // Log::info($request->all());

    /* 1) Validate incoming data ───────────────────────────────────────── */
    $validated = $request->validate([
        'order_id'                    => ['required', 'uuid', 'exists:orders,id'],          // ⬅ uuid
        'products'                    => ['required', 'array', 'min:1'],
        'products.*.order_item_id'    => ['required', 'uuid', 'exists:order_items,id'],     // ⬅ uuid
        'products.*.courier_quantity' => ['required', 'integer', 'min:1'],
    ]);

    /* 2) Retrieve the order */
    $order = Order::findOrFail($validated['order_id']);

    /* 3) Assign the courier and set status = «ожидание» ----------------- */
    $waitingStatusId = StatusDoc::where('name', 'ожидание')->value('id');  // ⬅ ищем id
    $order->courier_id = Auth::id();      // logged-in courier
    $order->status_id  = $waitingStatusId;   // ⬅ вместо жёсткого 3
    $order->save();

    /* 4) Update each order item ---------------------------------------- */
    foreach ($validated['products'] as $itemData) {
        $orderItem = OrderItem::where('id', $itemData['order_item_id'])
                              ->where('order_id', $order->id)
                              ->first();

        if ($orderItem) {
            $orderItem->courier_quantity = $itemData['courier_quantity'];
            $orderItem->save();
        }
    }

    /* 5) Respond -------------------------------------------------------- */
    return response()->json([
        'success' => true,
        'message' => 'Courier data updated successfully.',
        'order'   => $order->fresh('orderItems'),
    ], 201);
}



}
