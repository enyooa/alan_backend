<?php
namespace App\Http\Controllers;

use App\Models\GeneralWarehouse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackerDocument;
use App\Models\Sale;
use App\Models\StatusDoc;
use App\Models\Unit_measurement;
use App\Models\User;
use BeyondCode\LaravelWebSockets\Server\Loggers\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    // 1) Validate the incoming data, including your new fields if you like
    $validated = $request->validate([
        'order_id'                     => 'required|integer|exists:orders,id',
        'products'                     => 'required|array',
        'products.*.order_item_id'     => 'required|integer|exists:order_items,id',
        'products.*.packer_quantity'   => 'required|integer|min:1',
        // Optional: if you want to ensure these exist or are numeric, etc.
        // 'products.*.unit_measurement' => 'nullable|string',
        // 'products.*.price'            => 'nullable|numeric',
        // 'products.*.totalsum'         => 'nullable|numeric',
    ]);

    // 2) Retrieve the order
    $user = Auth::user();
    $order = Order::findOrFail($validated['order_id']);

    // 3) Assign the packer and update status
    $order->packer_id = $user->id;
    // Optionally, if you're using status_id instead of 'status',
    // do: $order->status_id = 2; // "processing" in your status_docs table
    // or if you're using a string-based column:
    $order->status_id = 2;
    $order->save();

    // 4) Update each order item's fields
    foreach ($validated['products'] as $itemData) {
        // Ensure this item truly belongs to the same order
        $orderItem = OrderItem::where('id', $itemData['order_item_id'])
            ->where('order_id', $order->id)
            ->first();

        if ($orderItem) {
            // Required field from validation
            $orderItem->packer_quantity = $itemData['packer_quantity'];

            // If you want to store unit_measurement, price, totalsum from the request:
            if (isset($itemData['unit_measurement'])) {
                $orderItem->unit_measurement = $itemData['unit_measurement'];
            }
            if (isset($itemData['price'])) {
                $orderItem->price = $itemData['price'];
            }
            if (isset($itemData['totalsum'])) {
                $orderItem->totalsum = $itemData['totalsum'];
            }

            $orderItem->save();
        }
    }

    // 5) Return success response with fresh data
    return response()->json([
        'success' => true,
        'message' => 'Invoice created successfully, packer assigned.',
        'order'   => $order->fresh('orderItems'),
    ], 201);
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


public function getAllInstances()
{
    try {
        // 1. Fetch ALL users (or whatever subset you want to return)
        //    For security, you may choose specific columns instead of all (*).
        $users = User::select('id', 'first_name', 'last_name', 'whatsapp_number')
            ->with('addresses:name') // If you want addresses for all users
            ->get();

        // 2. Fetch unit measurements (instead of = Unit_measurement::class, use the actual model query)
        $unit_measurements = Unit_measurement::select('id', 'name')->get();
        $status_docs = StatusDoc::all();
        // 3. Fetch only couriers
        $couriers = User::whereHas('roles', function ($query) {
                $query->where('name', 'courier');
            })
            ->with('addresses:name')
            ->get(['id', 'first_name', 'last_name', 'whatsapp_number']);

        // 4. Return them together in one response
        return response()->json([
            'users' => $users,
            'unit_measurements' => $unit_measurements,
            'couriers' => $couriers,
            'status' => $status_docs,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error'   => 'Failed to fetch data',
            'message' => $e->getMessage(),
        ], 500);
    }
}


public function getManagerWarehouseReport(Request $request)
{
    Log::info($request->all());
    $managerId = Auth::id(); // "packer_id"
    $dateFrom = $request->input('date_from');
    $dateTo   = $request->input('date_to');

    $report = DB::table('warehouses AS wh')
        ->join('warehouse_items AS wi', 'wi.warehouse_id', '=', 'wh.id')
        ->join('product_sub_cards AS psc', 'psc.id', '=', 'wi.product_subcard_id')
        ->leftJoin('documents AS d', function($join) {
            $join->on('d.to_warehouse_id', '=', 'wh.id')
                 ->orOn('d.from_warehouse_id', '=', 'wh.id');
        })
        ->leftJoin('document_types AS dt', 'dt.id', '=', 'd.document_type_id')
        ->leftJoin('document_items AS di', function($join) {
            $join->on('di.document_id', '=', 'd.id');
            $join->on('di.product_subcard_id', '=', 'psc.id');
        })
        ->select(
            'wh.id AS warehouse_id',
            'wh.name AS warehouse_name',
            'psc.id AS product_id',
            'psc.name AS product_name',
            'wi.quantity AS current_quantity',
            'wi.cost_price AS current_cost_price',
            // inbound
            DB::raw("
                SUM(
                    CASE WHEN (dt.code='income' AND d.to_warehouse_id=wh.id)
                           OR (dt.code='transfer' AND d.to_warehouse_id=wh.id)
                    THEN di.quantity ELSE 0 END
                ) AS total_inbound
            "),
            // outbound
            DB::raw("
                SUM(
                    CASE WHEN (dt.code='sale' AND d.from_warehouse_id=wh.id)
                           OR (dt.code='write_off' AND d.from_warehouse_id=wh.id)
                           OR (dt.code='transfer' AND d.from_warehouse_id=wh.id)
                    THEN di.quantity ELSE 0 END
                ) AS total_outbound
            ")
        )
        // Filter so only warehouses where packer_id == $managerId
        ->where('wh.packer_id', $managerId)
        // Optional date range
        ->when($dateFrom && $dateTo, function($query) use ($dateFrom, $dateTo) {
            return $query->whereBetween('d.document_date', [$dateFrom, $dateTo]);
        })
        ->groupBy('wh.id', 'psc.id', 'wi.quantity', 'wi.cost_price', 'wh.name', 'psc.name')
        ->orderBy('wh.id')
        ->orderBy('psc.id')
        ->get();

    // Transform to add remainder and remainder_value
    $report->transform(function($row) {
        $inbound   = $row->total_inbound ?? 0;
        $outbound  = $row->total_outbound ?? 0;
        // Could also do $row->current_quantity
        $stock     = $inbound - $outbound;
        $costPrice = $row->current_cost_price ?? 0;

        $row->remainder       = $stock;
        $row->remainder_value = $stock * $costPrice;
        return $row;
    });

    return response()->json($report);
}


}
