<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\GeneralWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehousesController extends Controller
{
    public function getRemainingQuantity(Request $request, $productSubcardId)
{
    try {
        $quantity = AdminWarehouse::where('product_subcard_id', $productSubcardId)->sum('quantity');

        return response()->json([
            'product_subcard_id' => $productSubcardId,
            'remaining_quantity' => $quantity,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error fetching remaining quantity.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    /**
     * Transfer inventory from AdminWarehouse to GeneralWarehouse
     */
    public function transferToGeneralWarehouse(Request $request)
    {
        $validatedData = $request->validate([
            'transfers' => 'required|array',
            'transfers.*.admin_warehouse_id' => 'required|exists:admin_warehouses,id',
            'transfers.*.quantity' => 'required|numeric|min:0',
            'address_id' => 'required|exists:addresses,id',
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validatedData['transfers'] as $transfer) {
                $adminWarehouse = AdminWarehouse::find($transfer['admin_warehouse_id']);

                if ($adminWarehouse->quantity < $transfer['quantity']) {
                    throw new \Exception('Insufficient quantity in AdminWarehouse for product: ' . $adminWarehouse->product_card_id);
                }

                // Deduct from AdminWarehouse
                $adminWarehouse->quantity -= $transfer['quantity'];
                $adminWarehouse->save();

                // Add to GeneralWarehouse
                GeneralWarehouse::create([
                    'organization_id' => $adminWarehouse->organization_id,
                    'product_subcard_id' => $adminWarehouse->product_card_id, // Assuming product_card_id aligns with product_subcard_id
                    'user_id' => $validatedData['user_id'],
                    'address_id' => $validatedData['address_id'],
                    'unit_measurement' => $adminWarehouse->unit_measurement,
                    'quantity' => $transfer['quantity'],
                    'price' => $adminWarehouse->price,
                    'total_sum' => $transfer['quantity'] * $adminWarehouse->price,
                    'date' => $validatedData['date'],
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Inventory successfully transferred.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
