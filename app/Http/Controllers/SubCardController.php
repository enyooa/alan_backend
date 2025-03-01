<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\ProductCard;
use App\Models\ProductSubCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubCardController extends Controller
{
    /**
     * Store a new ProductSubCard
     */
    public function store(Request $request)
    {
        try {
            Log::info('ProductSubCard store endpoint hit.', ['request' => $request->all()]);

            $validated = $request->validate([
                'product_card_id' => 'required|exists:product_cards,id',
                'name'           => 'required|string|max:255',
            ]);

            $subCard = ProductSubCard::create($validated);

            return response()->json([
                'message' => 'Подкарточка успешно создано!',
                'data'    => $subCard,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating product subcard.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create product subcard.'], 500);
        }
    }

    /**
     * Get all subcards, each with total quantity and batch details from AdminWarehouse
     */
    public function getSubCards()
    {
        try {
            $subCards = ProductSubCard::all()->map(function ($subCard) {
                // Retrieve all AdminWarehouse entries (batches) for this subcard
                $batches = AdminWarehouse::where('product_subcard_id', $subCard->id)->get();

                // Calculate the total quantity from all batches
                $totalQuantity = $batches->sum('quantity');

                // Map each batch to return its details
                $batchDetails = $batches->map(function ($batch) {
                    return [
                        'id'               => $batch->id,
                        'quantity'         => $batch->quantity,
                        'unit_measurement' => $batch->unit_measurement,
                        'price'            => $batch->price,
                        'cost_price'       => $batch->cost_price,
                        'date'             => $batch->date,
                        // Add other fields if needed...
                    ];
                });

                // Return the original subcard data along with the total quantity and batch details
                return array_merge($subCard->toArray(), [
                    'total_quantity' => $totalQuantity,
                    'batches'        => $batchDetails,
                ]);
            });

            return response()->json($subCards, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching subcards with batch details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch subcards with batch details.'], 500);
        }
    }

    /**
     * Fetch all subcards for a specific product card
     */
    public function fetchByProductCard($productCardId)
    {
        $subCards = ProductSubCard::where('product_card_id', $productCardId)->get();

        return response()->json([
            'data' => $subCards,
        ], 200);
    }

    /**
     * Update an existing ProductSubCard
     */
    public function update(Request $request, $id)
    {
        $productSubCard = ProductSubCard::findOrFail($id);

        $validated = $request->validate([
            'product_card_id' => 'nullable|integer|exists:product_cards,id',
            'name'  => 'nullable|string',
            'brutto'=> 'nullable|numeric',
            'netto' => 'nullable|numeric',
        ]);

        $productSubCard->update($validated);

        return response()->json(['message' => 'Product SubCard updated successfully'], 200);
    }

    /**
     * Delete a ProductSubCard
     */
    public function destroy($id)
    {
        ProductSubCard::destroy($id);
        return response()->json(['message' => 'Product SubCard deleted successfully'], 200);
    }
}
