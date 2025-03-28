<?php

namespace App\Http\Controllers;

use App\Models\CourierDocument;
use App\Models\Document;
use App\Models\FinancialOrder;
use App\Models\Order;
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


    public function getClientOrders()
    {
        $user = Auth::user(); // The currently authenticated user

        // 1) Fetch only the orders belonging to this client
        // 2) Eager-load 'orderItems.productSubCard' (or 'orderProducts') to get item details
        // 3) Return them as JSON
        $orders = Order::where('user_id', $user->id)
            ->with(['orderItems.productSubCard'])
            ->get();

        return response()->json($orders, 200);
    }

    public function report_debs()
{
    $userId = Auth::id(); // current authenticated user ID

    // 1) Retrieve documents where client_id = current user
    $documents = Document::with('documentItems')->where('client_id', $userId)->get();

    // 2) Retrieve financial orders where user_id = current user
    $financialOrders = FinancialOrder::where('user_id', $userId)->get();

    return response()->json([
        'documents'        => $documents,
        'financial_orders' => $financialOrders,
    ], 200);
}

}
