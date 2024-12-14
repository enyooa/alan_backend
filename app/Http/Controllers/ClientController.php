<?php

namespace App\Http\Controllers;

use App\Models\ProductCard;
use Illuminate\Http\Request;

class ClientController extends Controller
{

    public function getAllProductData()
    {
        try {
            $productCards = ProductCard::with(['subCards.sales'])->get();

            $data = $productCards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'name_of_products' => $card->name_of_products,
                    'description' => $card->description,
                    'photo_url' => $card->photo_url,
                    'subcards' => $card->subCards->map(function ($subCard) {
                        return [
                            'id' => $subCard->id,
                            'name' => $subCard->name,
                            'brutto' => $subCard->brutto,
                            'netto' => $subCard->netto,
                            'sales' => $subCard->sales->map(function ($sale) {
                                return [
                                    'id' => $sale->id,
                                    'price' => $sale->price,
                                    'quantity' => $sale->quantity,
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product data', 'error' => $e->getMessage()], 500);
        }
    }

    
}
