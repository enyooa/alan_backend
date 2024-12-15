<?php
namespace App\Http\Controllers;

use App\Models\GeneralWarehouse;
use App\Models\PackerDocument;
use App\Models\User;
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
    Log::info('Packer Document Request:', $request->all());

    try {
        // Validate the incoming request
        $validated = $request->validate([
            'id_courier' => 'required|integer|exists:users,id', // Ensure this is a valid courier ID
            'delivery_address' => 'nullable|string|max:255',
            'product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
            'amount_of_products' => 'required|numeric|min:1', // Ensure a positive product amount
        ]);

        // Create a new packer document
        $packerDocument = PackerDocument::create($validated);

        return response()->json([
            'message' => 'Документ успешно создан.',
            'data' => $packerDocument,
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create document',
            'message' => $e->getMessage(),
        ], 500);
    }
}

    public function get_packer_document(){
        return PackerDocument::all();
    }

}
