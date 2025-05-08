<?php

namespace App\Http\Controllers;

use App\Models\Basket;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FavoritesController extends Controller
{
    // app/Http/Controllers/FavoritesController.php
public function getFavorites(): JsonResponse
{
    $userId = Auth::id();

    $rows = Favorite::where('user_id', $userId)
        ->with(['productSubcard.productCard'])
        ->get();

    if ($rows->isEmpty()) {
        return response()->json(['favorites' => []]);
    }

    $payload = $rows->map(function (Favorite $fav) {
        return [
            // — data the basket screen might need —
            'product_subcard_id' => $fav->product_subcard_id,
            'source_table'       => $fav->source_table ?? 'favorites',
            'source_table_id'    => $fav->id,

            // — extra for the UI —
            'id'              => $fav->id,
            'price'           => $fav->price,
            'unit_measurement'=> $fav->unit_measurement,
            'totalsum'        => $fav->totalsum,
            'created_at'      => $fav->created_at,

            'product' => [
                'subcard_name'     => $fav->productSubcard->name                ?? null,
                'product_card'     => [
                    'name_of_products' => $fav->productSubcard->productCard->name_of_products ?? null,
                    'description'      => $fav->productSubcard->productCard->description      ?? null,
                    'photo_product'    => $fav->productSubcard->productCard->photo_product    ?? null,
                ],
            ],
        ];
    });

    return response()->json(['favorites' => $payload]);
}

    /* -----------------------------------------------------------
     * 1. add / toggle
     * --------------------------------------------------------- */
    public function addToFavorites(Request $request): JsonResponse
{
    $data = $request->validate([
        'product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],
        'price'              => ['required','numeric','gte:0'],
        'unit_measurement'   => ['required','string','max:255'],
        'source_table'       => ['nullable','string'],
    ]);

    $user = Auth::user();

    $favorite = Favorite::updateOrCreate(
        [
            'organization_id'    => $user->organization_id,
            'user_id'            => $user->id,
            'product_subcard_id' => $data['product_subcard_id'],
        ],
        [
            'source_table'       => $data['source_table'] ?? 'favorites',
            'price'              => $data['price'],
            'unit_measurement'   => $data['unit_measurement'],
            'totalsum'           => $data['price'],             // qty is implicitly 1
        ]
    );

    return response()->json(['success'=>true,'favorite'=>$favorite],201);
}


    /* -----------------------------------------------------------
     * 2. remove
     * --------------------------------------------------------- */
    public function destroy(Favorite $favorite): JsonResponse
    {
        // ❗ запись должна принадлежать текущему пользователю
        if ($favorite->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'error'   => 'Forbidden',
            ], 403);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites',
        ]);
    }


    /* -----------------------------------------------------------
     * 3. list
     * --------------------------------------------------------- */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        $rows = Favorite::where('user_id', $userId)
            ->with(['productSubCard.productCard'])
            ->get();

        $payload = $rows->map(function (Favorite $fav) {
            return [
                /* --- minimal info the basket needs ---------------- */
                'product_subcard_id' => $fav->product_subcard_id,
                'source_table'       => $fav->source_table ?? 'favorites',
                'source_table_id'    => $fav->id,

                /* --- extra details for the UI -------------------- */
                'id'           => $fav->id,
                'created_at'   => $fav->created_at,
                'product'      => [
                    'subcard_name'     => $fav->productSubCard->name ?? null,
                    'product_card'     => [
                        'name_of_products' => $fav->productSubCard->productCard->name_of_products ?? null,
                        'description'      => $fav->productSubCard->productCard->description      ?? null,
                        'photo_product'    => $fav->productSubCard->productCard->photo_product    ?? null,
                    ],
                ],
            ];
        });

        return response()->json(['favorites' => $payload]);
    }

    public function addToBasket(Request $request, Favorite $favorite): JsonResponse
    {
        // 1️⃣  the favourite must belong to the caller
        if ($favorite->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // 2️⃣  validate *optional* overrides coming from the client
        $data = $request->validate([
            'quantity'         => ['sometimes', 'numeric', 'gt:0'],
            'price'            => ['sometimes', 'numeric', 'gte:0'],
            'unit_measurement' => ['sometimes', 'string',  'max:255'],
        ]);

        // 3️⃣  normalise input
        $qty   = $data['quantity']         ?? 1;                     // default 1
        $price = $data['price']            ?? $favorite->price;      // fall back to saved price
        $unit  = $data['unit_measurement'] ?? $favorite->unit_measurement;
        if ($price === null) {                                        // still null? → error
            return response()->json(['error' => 'Price is required'], 422);
        }

        DB::beginTransaction();
        try {
            // 4️⃣  look for an existing basket row for the same sub-card & favourite
            $basket = Basket::lockForUpdate()->firstOrNew([
                'id_client_request'  => Auth::id(),
                'product_subcard_id' => $favorite->product_subcard_id,
                'source_table'       => 'favorites',
                'source_table_id'    => $favorite->id,
            ]);

            // --- update or fill ---
            $basket->quantity        = ($basket->exists ? $basket->quantity : 0) + $qty;
            $basket->price           = $price;
            $basket->unit_measurement= $unit;
            $basket->totalsum        = $basket->quantity * $price;
            $basket->save();

            DB::commit();
            return response()->json([
                'success'     => true,
                'basket_item' => $basket->fresh()   // return latest data
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('addToBasket (favorites) failed', ['msg' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error'   => 'Could not add item to basket',
            ], 500);
        }
    }

    public function removeFromFavorites(Request $request): JsonResponse
    {
        /* 1️⃣  Accept either favourite row ID *or* product_subcard_id */
        $data = $request->validate([
            'id'                 => ['required_without:product_subcard_id','uuid','exists:favorites,id'],
            'product_subcard_id' => ['required_without:id','uuid','exists:favorites,product_subcard_id'],
        ]);

        /* 2️⃣  Target only this user’s favourites */
        $query = Favorite::where('user_id', Auth::id());

        isset($data['id'])
            ? $query->where('id', $data['id'])
            : $query->where('product_subcard_id', $data['product_subcard_id']);

        /* 3️⃣  Delete and respond */
        $deleted = $query->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error'   => 'Favorite not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites',
        ]);
    }
}
