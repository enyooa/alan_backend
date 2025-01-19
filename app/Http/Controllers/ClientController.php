<?php

namespace App\Http\Controllers;

use App\Models\CourierDocument;
use App\Models\OrderItem;
use App\Models\ProductCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    
    public function getClientOrderItems()
{
    $clientId = Auth::id();

    // Validate the authenticated user is a client
    if (!Auth::user()->isClient()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Fetch `order_items` linked to the client
    $orderItems = OrderItem::whereHas('order', function ($query) use ($clientId) {
        $query->where('user_id', $clientId);
    })
    ->with(['order.client', 'productSubCard', 'courierDocument.courier'])
    ->get();

    // Group by `courier_document_id`
    $uniqueDocuments = $orderItems->groupBy('courier_document_id')->map(function ($items) {
        $item = $items->first(); // Take the first item for each document
        return [
            'order_item_id' => $item->id,
            'product_subcard_id' => $item->product_subcard_id,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'order' => [
                'order_id' => $item->order->id,
                'client' => [
                    'id' => $item->order->client->id,
                    'name' => $item->order->client->first_name . ' ' . $item->order->client->last_name,
                ],
                'address' => $item->order->address,
            ],
            'courier_document' => $item->courierDocument ? [
                'id' => $item->courierDocument->id,
                'is_confirmed' => $item->courierDocument->is_confirmed,
                'courier' => [
                    'id' => $item->courierDocument->courier->id,
                    'name' => $item->courierDocument->courier->first_name . ' ' . $item->courierDocument->courier->last_name,
                ],
            ] : null,
        ];
    })->values(); // Convert to a simple array

    return response()->json($uniqueDocuments);
}

      
}
