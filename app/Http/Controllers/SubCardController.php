<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\Document;
use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\ReferenceItem;
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
     * old version
     */
    public function getSubCards()
    {
        try {
            $subCards = ProductSubCard::all();

            return response()->json($subCards, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching subcards with batch details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch subcards with batch details.'], 500);
        }
    }


    public function getProductSubcards(Request $request)
{
    try {
        /* -----------------------------
         | 1) базовый запрос + optional фильтр
         * ---------------------------*/
        $items = ReferenceItem::query()
            ->when(
                $request->filled('reference_id'),
                fn ($q) => $q->where('reference_id', $request->reference_id)
            )
            ->with('reference:id,title')   // подтягиваем название карточки
            ->get();

        /* -----------------------------
         | 2) подготовка payload
         * ---------------------------*/
        $payload = $items->map(fn ($it) => [
            'id'              => $it->id,
            'reference_id'    => $it->reference_id,
            'reference_title' => $it->reference?->title,
            'name'            => $it->name,
            'value'           => $it->value,
        ]);

        return response()->json($payload, 200);

    } catch (\Throwable $e) {
        \Log::error('Error fetching card items', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to fetch card items.'], 500);
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
