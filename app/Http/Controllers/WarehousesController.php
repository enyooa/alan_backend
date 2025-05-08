<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\Expense;
use App\Models\GeneralWarehouse;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Unit_measurement;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehousesController extends Controller
{

    // app/Http/Controllers/WarehouseController.php
public function getWarehouses(Request $request)
{
    /** @var \App\Models\User $user */
    $user = $request->user();                         // авторизованный
    $org  = $user->organization_id;                 // UUID

    $items = Warehouse::where('organization_id', $org)
                      ->orderBy('name')
                      ->get();

    return response()->json($items);                  // 200 по-умолчанию
}


    // In WarehouseItemController
public function getWarehouseItems(Request $request)
{
    $warehouseId = $request->query('warehouse_id');
    if (!$warehouseId) {
        return response()->json([], 200);
    }

    $items = WarehouseItem::where('warehouse_id', $warehouseId)->get();

    // Build a simple array of leftovers
    $result = [];
    foreach ($items as $whItem) {
        // Optionally grab product name from product_sub_cards
        $product = DB::table('product_sub_cards')
            ->where('id', $whItem->product_subcard_id)
            ->select('id','name')
            ->first();

        $result[] = [
            'product_subcard_id' => $whItem->product_subcard_id,
            'name'               => $product ? $product->name : ('Unknown #'.$whItem->product_subcard_id),
            'balance'            => $whItem->quantity,
            'unit_measurement'   => $whItem->unit_measurement,
        ];
    }

    return response()->json($result, 200);
}

//     public function getRemainingQuantity(Request $request, $productSubcardId)
// {
//     try {
//         $quantity = AdminWarehouse::where('product_subcard_id', $productSubcardId)->sum('quantity');

//         return response()->json([
//             'product_subcard_id' => $productSubcardId,
//             'remaining_quantity' => $quantity,
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => 'Error fetching remaining quantity.',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }



    /**
     * Transfer inventory from AdminWarehouse to GeneralWarehouse
     */

     public function getWarehouseDetails(Request $request)
    {

        $productSubCards = ProductSubCard::all();
        $unitMeasurements = Unit_measurement::all();
        $providers = Provider::all();
        $expenses = Expense::all();
        $warehouses = Warehouse::all();
        $warehouseItems = WarehouseItem::all();

        return response()->json([
            'providers' => $providers,
            'product_sub_cards' => $productSubCards,
            'unit_measurements' => $unitMeasurements,
            'expenses' => $expenses,
            'warehouses' => $warehouses,
            'warehouseItems' => $warehouseItems,
        ]);
    }

}
