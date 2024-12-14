<?php

namespace App\Http\Controllers;

use App\Models\ProductCard;
use App\Models\ProductSubCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class SubCardController extends Controller
{
    // Store a new ProductSubCard
    public function store(Request $request)
{
    try {
        Log::info('ProductSubCard store endpoint hit.', ['request' => $request->all()]);

        $validated = $request->validate([
            'product_card_id' => 'required|exists:product_cards,id',
            'name' => 'required|string|max:255',
            'brutto' => 'required|numeric|min:0',
            'netto' => 'required|numeric|min:0',
        ]);

        $subCard = ProductSubCard::create($validated);

        return response()->json([
            'message' => 'Подкарточка успешно создано!',
            'data' => $subCard,
        ], 201);
    } catch (\Exception $e) {
        Log::error('Error creating product subcard.', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create product subcard.'], 500);
    }
}

    public function getSubCards()
{
    try {
        $subCards = ProductSubCard::all(); // Retrieve all subcards
        return response()->json($subCards, 200);
    } catch (\Exception $e) {
        Log::error('Error fetching subcards', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to fetch subcards.'], 500);
    }
}

    // Fetch all subcards for a specific product card
    public function fetchByProductCard($productCardId)
    {
        $subCards = ProductSubCard::where('product_card_id', $productCardId)->get();

        return response()->json([
            'data' => $subCards,
        ], 200);
    }
}
