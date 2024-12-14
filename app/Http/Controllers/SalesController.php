<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
class SalesController extends Controller
{
    public function getSalesWithDetails()
{
    $sales = Sale::with(['subCard.productCard'])->get();

    return response()->json($sales);
}

    public function store(Request $request)
    {
        // Log the raw incoming data for debugging
        Log::info('Received sale data:', $request->all());

        // Validate the incoming request
        $validated = $request->validate([
            'product_subcard_id' => 'required|integer|exists:product_subcards,id', // Ensure the subcard exists
            'unit_measurement' => 'nullable|string|max:255',
            'amount' => 'required|integer|min:1', // Ensure amount is positive
            'price' => 'required|integer|min:1', // Ensure price is positive
        ]);

        // Create the sale record in the database
        $sale = Sale::create($validated);

        // Respond with success and the created sale data
        return response()->json([
            'message' => 'Sale successfully created!',
            'data' => $sale,
        ], 201);
    }

    public function bulkStore(Request $request)
{
    // Log the incoming request for debugging
    Log::info('Received bulk sales data:', $request->all());

    // Validate the incoming data
    $validated = $request->validate([
        'sales' => 'required|array',
        'sales.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
        'sales.*.unit_measurement' => 'nullable|string|max:255',
        'sales.*.amount' => 'integer|min:1',
        'sales.*.price' => 'integer|min:1',
    ]);

    try {
        // Insert the validated sales data into the database
        foreach ($validated['sales'] as $sale) {
            Sale::create([
                'product_subcard_id' => $sale['product_subcard_id'],
                'unit_measurement' => $sale['unit_measurement'],
                'amount' => $sale['amount'],
                'price' => $sale['price'],
            ]);
        }

        // Return success response
        return response()->json([
            'message' => 'Продажи успешно созданы!',
        ], 201);
    } catch (\Exception $e) {
        // Log and return the error if insertion fails
        Log::error('Error saving sales:', ['error' => $e->getMessage()]);
        return response()->json([
            'message' => 'Ошибка при сохранении продаж.',
            'error' => $e->getMessage(),
        ], 500);
    }
}




}
