<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;

class FavoritesController extends Controller
{
    public function addToFavorites(Request $request)
    {
        $validated = $request->validate([
            'product_subcard_id' => 'required|exists:products,id',
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => Auth::id(),
            'product_subcard_id' => $validated['product_id'],
        ]);

        return response()->json(['success' => true, 'favorite' => $favorite]);
    }

    public function removeFromFavorites(Request $request)
    {
        $validated = $request->validate([
            'product_subcard_id' => 'required|exists:favorites,product_subcard_id',
        ]);

        $favorite = Favorite::where([
            'user_id' => Auth::id(),
            'product_subcard_id' => $validated['product_subcard_id'],
        ])->delete();

        return response()->json(['success' => true, 'message' => 'Product removed from favorites']);
    }

    public function getFavorites()
    {
        $favorites = Favorite::where('user_id', Auth::id())->get();

        return response()->json(['favorites' => $favorites]);
    }
}
