<?php
namespace App\Http\Controllers;

use App\Models\GeneralWarehouse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackerDocument;
use App\Models\Sale;
use App\Models\User;
use BeyondCode\LaravelWebSockets\Server\Loggers\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackerController extends Controller
{
    public function generalWarehouse(Request $request)
    {
        try {
            // Validate the incoming date parameters
            $validated = $request->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date',
            ]);
    
            // Query the general_warehouses table with filtering
            $data = DB::table('general_warehouses')
                ->join('product_sub_cards', 'general_warehouses.product_subcard_id', '=', 'product_sub_cards.id')
                ->select(
                    'general_warehouses.id',
                    'general_warehouses.amount',
                    'general_warehouses.unit_measurement',
                    'general_warehouses.date',
                    'product_sub_cards.name as product_subcard_name'
                )
                ->whereBetween('general_warehouses.date', [$validated['startDate'], $validated['endDate']])
                ->get();
    
            return response()->json($data, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data', 'message' => $e->getMessage()], 500);
        }
    }


    // накладная
    public function create_packer_document(Request $request)
{
    Log::info($request->all());

    $validatedData = $request->validate([
        'id_courier' => 'required|integer|exists:users,id',
        'delivery_address' => 'required|string',
        'order_products' => 'required|array',
        'order_products.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
        'order_products.*.source_table_id' => 'required|integer|exists:order_items,source_table_id',
        'order_products.*.quantity' => 'required|integer|min:1',
        'order_products.*.price' => 'required|numeric|min:0',
        'order_id' => 'required|integer|exists:orders,id', // Validate order_id
    ]);

    DB::beginTransaction();

    try {
        // Update the order with the courier_id and packer_document_id
        $order = Order::findOrFail($validatedData['order_id']);
        $order->courier_id = $validatedData['id_courier'];
        $order->packer_document_id = $validatedData['order_id']; // Assuming `packer_document_id` is set as the `order_id`
        $order->save();

        // Update order items (if needed for additional logic)
        foreach ($validatedData['order_products'] as $product) {
            $orderItem = OrderItem::where([
                'order_id' => $validatedData['order_id'],
                'product_subcard_id' => $product['product_subcard_id'],
                'source_table_id' => $product['source_table_id'],
            ])->first();

            if ($orderItem) {
                $orderItem->update([
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Document created and courier assigned successfully.',
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error creating packer document: ', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}


    
    

// Helper function to determine the source_table_id
// private function getSourceTableId($productSubcardId, $quantity)
// {
//     // Example logic to fetch the correct `source_table_id` based on inventory or business rules
//     $source = Sale::where('product_subcard_id', $productSubcardId)
//         ->where('amount', '>=', $quantity)
//         ->first();

//     if ($source) {
//         // Deduct the quantity from the source and return its ID
//         $source->amount -= $quantity;
//         $source->save();

//         return $source->id;
//     }

//     return null; // Handle the case where no valid source is found
// }

public function get_packer_document($packerDocumentId)
{
    $packerDocument = PackerDocument::with([
        'orderItems.productSubCard.productCard', // Eager load relationships for order items
    ])->find($packerDocumentId);

    if (!$packerDocument) {
        return response()->json([
            'success' => false,
            'message' => 'Packer Document not found.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $packerDocument,
    ]);
}




}
