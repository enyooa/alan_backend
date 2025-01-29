<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use App\Models\GeneralWarehouse;
use App\Models\ProductSubCard;
use App\Models\Unit_measurement;
use App\Models\User;
use DocumentsRequests;
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
                $query->where('name', 'storager');
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


    public function getAllInstances(Request $request)
    {
        
        $clientsAndAddresses = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->with('addresses')->get(['id', 'first_name', 'last_name', 'surname']);
    
        $productSubCards = ProductSubCard::all();
        $unitMeasurements = Unit_measurement::all(['id', 'name']);

        // Return JSON response
        return response()->json([
            'clients' => $clientsAndAddresses,
            'product_sub_cards' => $productSubCards,
            'unit_measurements' => $unitMeasurements,
        ]);
    }
    public function storeSales(Request $request)
    {
        Log::info($request->all());
    
        // Validate the incoming request
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'products' => 'required|array',
            'products.*.product_subcard_id' => 'required|integer',
            'products.*.price' => 'nullable|integer',
            'products.*.unit_measurement_id' => 'required|integer',
            'products.*.quantity' => 'nullable|integer',
            'products.*.brutto' => 'nullable|integer',
            'products.*.netto' => 'nullable|integer',
        ]);
    
        try {
            // Iterate through each product and save it
            foreach ($validated['products'] as $product) {
                DocumentRequest::create([
                    'client_id' => $validated['client_id'],
                    'product_subcard_id' => $product['product_subcard_id'],
                    'price' => $product['price'] ?? null,
                    'unit_measurement_id' => $product['unit_measurement_id'],
                    'amount' => $product['quantity'] ?? null, // Match `quantity` to `amount`
                    'brutto' => $product['brutto'] ?? null,
                    'netto' => $product['netto'] ?? null,
                ]);
            }
    
            return response()->json(['message' => 'Data successfully saved'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
   
    public function fetchSalesReport()
{
    $sales = DocumentRequest::with('productSubcard', 'unitMeasurement')
        ->get()
        ->map(function ($sale) {
            $remainingQuantity = GeneralWarehouse::where('product_subcard_id', $sale->product_subcard_id)
                ->sum('quantity');

            return [
                'product' => $sale->productSubcard->name ?? 'Unknown',
                'unit' => $sale->unitMeasurement->name ?? 'Unknown',
                'quantity' => $sale->amount ?? 0,
                'price' => $sale->price ?? 0,
                'total' => ($sale->amount ?? 0) * ($sale->price ?? 0),
                'remaining' => $remainingQuantity ?? 0, // Add remaining quantity
            ];
        });

    return response()->json(['sales' => $sales]);
}

public function storageReceivingBulkStore(Request $request)
{
    try {
        Log::info('Received bulk storage receiving data:', $request->all());

        // Validation for the expected fields
        $validated = $request->validate([
            'receivings' => 'required|array',
            'receivings.*.subcard_id' => 'required|integer',
            'receivings.*.unit_name' => 'required|string|max:255',
            'receivings.*.quantity' => 'required|numeric|min:0',
            'receivings.*.price' => 'required|numeric|min:0',
            'receivings.*.date' => 'required|date_format:Y-m-d',
        ]);

        // Iterate over each receiving and store it in the database
        foreach ($validated['receivings'] as $receiving) {
            GeneralWarehouse::create([
                'product_subcard_id' => $receiving['subcard_id'], // Use subcard_id instead of product_name
                'unit_measurement' => $receiving['unit_name'], // Use unit_name
                'quantity' => $receiving['quantity'],
                'price' => $receiving['price'],
                'total_sum' => $receiving['quantity'] * $receiving['price'], // Calculate total sum
                'date' => $receiving['date'],
                // Add other fields as needed (e.g., organization_id, user_id, address_id)
            ]);
        }

        return response()->json([
            'message' => 'Bulk storage receiving successfully stored!',
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error saving storage receiving:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Failed to store storage receiving data',
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function generalWarehouses()
{
    try {
        // Fetch all GeneralWarehouse records with related ProductSubCard
        $generalWarehouses = GeneralWarehouse::with('productSubCard:id,name')->get();

        // Map data to include product name and quantity
        $data = $generalWarehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'product_subcard_id' => $warehouse->product_subcard_id,
                'product_name' => $warehouse->productSubCard->name ?? 'Unknown Product',
                'quantity' => $warehouse->quantity ?? 0,
                'unit_measurement' => $warehouse->unit_measurement,
                'price' => $warehouse->price,
                'total_sum' => $warehouse->total_sum,
                'date' => $warehouse->date,
            ];
        });

        return response()->json($data, 200);
    } catch (\Exception $e) {
        Log::error('Error fetching general warehouse data:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Failed to fetch general warehouse data',
            'message' => $e->getMessage(),
        ], 500);
    }
}



    /**
     * Process write-offs for General Warehouse.
     */
    public function writeOff(Request $request)
{
    try {
        $validated = $request->validate([
            'write_offs' => 'required|array',
            'write_offs.*.product_subcard_id' => 'required|exists:general_warehouses,product_subcard_id',
            'write_offs.*.quantity' => 'required|numeric|min:0',
        ]);

        foreach ($validated['write_offs'] as $writeOff) {
            $warehouse = GeneralWarehouse::where('product_subcard_id', $writeOff['product_subcard_id'])->first();

            if ($warehouse && $warehouse->quantity >= $writeOff['quantity']) {
                $warehouse->quantity -= $writeOff['quantity'];
                $warehouse->save();
            } else {
                return response()->json(['error' => 'Insufficient stock for product.'], 422);
            }
        }

        return response()->json(['message' => 'Write-off processed successfully.'], 200);
    } catch (\Exception $e) {
        Log::error('Write-off error:', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to process write-off.'], 500);
    }
}


}
