<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneralWarehouse;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StorageController extends Controller
{
    /**
     * Get all storage users with "storage" role.
     */
    public function getStorageUsers()
    {
        try {
            $storageUsers = User::whereHas('roles', function ($query) {
                $query->where('name', 'storage');
            })->get(['id', 'first_name', 'last_name', 'surname','address']);

            return response()->json($storageUsers, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching storage users:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch storage users'], 500);
        }
    }


    /**
     * Bulk store inventory data to general_warehouses.
     */
    public function bulkStoreInventory(Request $request)
{
    Log::info($request->all());

    try {
        // Validate the incoming data
        $validated = $request->validate([
            'storager_id' => 'required|integer|exists:users,id', // Validate storager ID
            'address_id' => 'required|integer|exists:addresses,id', // Validate address ID
            'date' => 'required|date', // Validate date
            'inventory' => 'required|array',
            'inventory.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
            'inventory.*.amount' => 'nullable|numeric|min:0',
            'inventory.*.unit_measurement' => 'nullable|string|max:255',
        ]);

        Log::info('Validated bulk inventory data:', $validated);

        // Insert inventory data
        foreach ($validated['inventory'] as $inventory) {
            GeneralWarehouse::create([
                'storager_id' => $validated['storager_id'],
                'address_id' => $validated['address_id'], // Save address ID
                'product_subcard_id' => $inventory['product_subcard_id'],
                'amount' => $inventory['amount'] ?? 0,
                'unit_measurement' => $inventory['unit_measurement'] ?? null,
                'date' => $validated['date'], // Save date
            ]);
        }

        return response()->json([
            'message' => 'Инвентаризация успешно сохранена!',
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error saving inventory:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Failed to save inventory',
            'message' => $e->getMessage(),
        ], 500);
    }
}

    

    /**
     * Get inventory for a specific storager.
     */
    public function getInventory(Request $request)
    {
        try {
            $storagerId = $request->query('storager_id');

            $query = GeneralWarehouse::query();
            if ($storagerId) {
                $query->where('storager_id', $storagerId);
            }

            $inventories = $query->get();

            return response()->json($inventories, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch inventory'], 500);
        }
    }

    /**
     * Delete an inventory item from general_warehouses.
     */
    public function deleteInventory($id)
    {
        try {
            $inventory = GeneralWarehouse::findOrFail($id);
            $inventory->delete();

            return response()->json([
                'message' => 'Inventory item deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting inventory:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete inventory'], 500);
        }
    }
}
