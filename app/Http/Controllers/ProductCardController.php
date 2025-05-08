<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCard;
use App\Services\ProductCardService;
use Illuminate\Support\Facades\Log;

class ProductCardController extends Controller
{
    protected $productCardService;

    public function __construct(ProductCardService $productCardService)
    {
        $this->productCardService = $productCardService;
    }

    /**
     * Store a new ProductCard
     */
    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'name_of_products' => 'required|string|max:255',
            'description'      => 'nullable|string',
            'country'          => 'nullable|string|max:255',
            'type'             => 'nullable|string|max:255',
            'photo_product'    => 'nullable|file|mimes:jpeg,png,jpg,gif',
        ]);

        /* ─── 1. подписываем организацию ─── */
        $validated['organization_id'] = $request->user()->organization_id;

        /* ─── 2. обрабатываем фото ─── */
        if ($request->hasFile('photo_product')) {
            $validated['photo_product'] =
                $request->file('photo_product')->store('products', 'public');
        }

        $product = ProductCard::create($validated);

        return response()->json([
            'message' => 'Карточка товара успешно создана!',
            'data'    => $product,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error creating product card.', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create product card.'], 500);
    }
}


    /**
     * Get all ProductCards
     */
    public function getCardProducts()
    {
        try {
            $products = ProductCard::all();

            $products = $products->map(function ($product) {
                if ($product->photo_product) {
                    // Generate a full URL for the stored image
                    $product->photo_url = url('storage/' . $product->photo_product);
                } else {
                    $product->photo_url = null;
                }
                return $product;
            });

            return response()->json($products, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing ProductCard (HEAD version)
     */
    public function updateProductCard(Request $request, $id)
    {
        $productCard = ProductCard::findOrFail($id);

        $validated = $request->validate([
            'name_of_products' => 'nullable|string',
            'description'      => 'nullable|string',
            'country'          => 'nullable|string',
            'type'             => 'nullable|string',
            'photo_product'    => 'nullable|string',
        ]);

        $productCard->update($validated);

        return response()->json([
            'message' => 'Product Card updated successfully'
        ], 200);
    }

    /**
     * Delete a ProductCard
     */
    public function destroy($id)
    {
        ProductCard::destroy($id);
        return response()->json([
            'message' => 'Product Card deleted successfully'
        ], 200);
    }
}
