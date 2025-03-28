<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackerDocument;
use App\Models\StatusDoc;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
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
    try {
        // 1) Fetch orders where packer_document_id is NULL
        $orders = Order::with(['orderProducts.productSubCard.productCard'])
            // ->whereNull('packer_id')
            ->get();

        // 2) Fetch all statuses
        $statuses = StatusDoc::all();

        // 3) Return both in one JSON response
        return response()->json([
            'success' => true,
            'orders'  => $orders,
            'status'  => $statuses,  // add statuses array here
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function getHistoryOrders()
{
    // Fetch orders where packer_document_id is not null
    $orders = Order::with(['orderProducts.productSubCard.productCard'])
        ->whereNotNull('packer_id')
        ->orderBy('created_at', 'desc')
        ->get();

    // Fetch statuses from the status_docs table (adjust table/column names as needed)
    $statuses = StatusDoc::select('id', 'name')->get();

    return response()->json([
        'success'  => true,
        'orders'   => $orders,
        'statuses' => $statuses  // return statuses array here
    ], 200);
}







public function getDetailedOrder($orderId)
{
    // $packerId = Auth::id();

    $order = Order::where('id', $orderId)
        // ->where('packer_id', $packerId)
        ->with([
            'orderProducts.productSubCard.productCard',
             // Include the source relationship
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

public function confirmOrder($orderId)
    {
        // 1) Find the order with its items
        $order = Order::with('orderItems')->findOrFail($orderId);

        // 2) Update order => status_id = 4 ('исполнено')
        $order->status_id = 4;
        $order->save();

        // 3) Retrieve the packer_id from the order
        $packerId = $order->packer_id;
        if (!$packerId) {
            return response()->json([
                'success' => false,
                'message' => 'This order has no packer_id. Cannot confirm.',
            ], 400);
        }

        // 4) Find the warehouse associated with that packer_id
        $warehouse = Warehouse::where('packer_id', $packerId)->first();
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => "No warehouse found for packer_id={$packerId}",
            ], 404);
        }

        // 5) Create a new Document (sale => document_type_id=3).
        // Adjust fields as needed: from_warehouse_id, client_id, etc.
        $document = Document::create([
            'document_type_id'  => 3,         // 3 => sale
            'from_warehouse_id' => $warehouse->id,
            'client_id'         => $order->user_id,  // Or some other user reference
            'status'            => 'confirmed',
            'worker_user_id'    => $packerId,
            'document_date'     => now(),
            'comments'          => "Sale from Order #{$order->id}",
        ]);

        // 6) For each item in the order, subtract from warehouse_items & create doc items
        foreach ($order->orderItems as $item) {
            // 6a) Find the matching warehouse item row
            $warehouseItem = WarehouseItem::where('warehouse_id', $warehouse->id)
                ->where('product_subcard_id', $item->product_subcard_id)
                ->where('unit_measurement', $item->unit_measurement)
                ->first();

            // 6b) Subtract the order quantity from the warehouse item
            if ($warehouseItem) {
                $warehouseItem->quantity -= $item->quantity;
                if ($warehouseItem->quantity < 0) {
                    $warehouseItem->quantity = 0; // or throw an error if you disallow negative
                }
                $warehouseItem->save();
            }

            // 6c) Create the DocumentItem, copying fields from warehouseItem to avoid null
            DocumentItem::create([
                'document_id'         => $document->id,
                'product_subcard_id'  => $item->product_subcard_id,
                'unit_measurement'    => $item->unit_measurement,
                'quantity'            => $item->quantity,
                'price'               => $item->price,
                'total_sum'           => $item->price * $item->quantity,

                // Copy from warehouseItem if you want same brutto/netto, or from $item if it has them
                'brutto'             => optional($warehouseItem)->brutto ?? 0,
                'netto'              => optional($warehouseItem)->netto ?? 0,
                'cost_price'         => optional($warehouseItem)->cost_price ?? 0,
                'additional_expenses'=> optional($warehouseItem)->additional_expenses ?? 0,
            ]);
        }

        // 7) Return success response
        return response()->json([
            'success' => true,
            'message' => 'Заказ подтвержден (исполнено), документ создан (продажа).',
            'document_id' => $document->id,
        ], 200);
    }

}
