<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FavoritesController extends Controller
{
    // Add to Favorites
    public function addToFavorites(Request $request)
{
    Log::info($request->all());
    $validated = $request->validate([
        'product_subcard_id' => 'required|exists:product_sub_cards,id',
        'source_table' => 'nullable|string', // Optional source_table
    ]);

    $favorite = Favorite::updateOrCreate(
        [
            'user_id' => Auth::id(),
            'product_subcard_id' => $validated['product_subcard_id'],
        ],
        [
            'source_table' => $validated['source_table'], // Update source_table
        ]
    );

    return response()->json(['success' => true, 'favorite' => $favorite], 201);
}


    // Remove from Favorites
    public function removeFromFavorites(Request $request)
{
    $validated = $request->validate([
        'product_subcard_id' => 'required|exists:favorites,product_subcard_id',
    ]);

    $deleted = Favorite::where([
        'user_id' => Auth::id(),
        'product_subcard_id' => $validated['product_subcard_id'],
    ])->delete();

    return response()->json(['success' => true, 'message' => 'Removed from favorites'], 200);
}


    // Get Favorites
    public function getFavorites()
    {
        $userId = Auth::id();
    
        $favorites = Favorite::where('user_id', $userId)
            ->with(['productSubcard.productCard'])
            ->get();
    
        $favoriteData = $favorites->map(function ($favorite) {
            return [
                'id' => $favorite->id,
                'product_subcard_id' => $favorite->product_subcard_id,
                'source_table' => $favorite->source_table ?? 'favorites', // Default to 'favorites'
                'source_table_id' => $favorite->id, // Use the favorite ID as the source_table_id
                'product_details' => [
                    'subcard_name' => $favorite->productSubcard->name ?? null,
                    'product_card' => [
                        'name_of_products' => $favorite->productSubcard->productCard->name_of_products ?? null,
                        'description' => $favorite->productSubcard->productCard->description ?? null,
                        'photo_product' => $favorite->productSubcard->productCard->photo_product ?? null,
                    ],
                ],
            ];
        });
    
        return response()->json(['favorites' => $favoriteData], 200);
    }
    

}
