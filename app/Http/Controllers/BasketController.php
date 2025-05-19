<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Basket;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StatusDoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BasketController extends Controller
{
    /**
     * Get all items in the user's basket.
     */
    public function index()
{
    $userId = Auth::id();

    $basketItems = Basket::where('id_client_request', $userId)
        ->with(['productSubCard.productCard'])
        ->get();

    // если корзина пуста – просто []
    if ($basketItems->isEmpty()) {
        return response()->json(['basket' => []], 200);
    }

    $basketData = $basketItems->map(function ($item) {
        return [
            'id'                  => $item->id,
            'quantity'            => $item->quantity,
            'price'               => $item->price ?? 0,
            'product_subcard_id'  => $item->product_subcard_id,
            'source_table'        => $item->source_table,
            'source_table_id'     => $item->source_table_id,   // ← ДОБАВИЛИ ЭТО
            'delivery_date'       => $item->delivery_date,
            'product_details'     => [
                'subcard_name' => $item->productSubCard->name ?? null,
                'brutto'       => $item->productSubCard->brutto ?? null,
                'netto'        => $item->productSubCard->netto ?? null,
                'product_card' => [
                    'name_of_products' => $item->productSubCard->productCard->name_of_products ?? null,
                    'description'      => $item->productSubCard->productCard->description ?? null,
                    'photo_product'    => $item->productSubCard->productCard->photo_product ?? null,
                ],
            ],
        ];
    });

    return response()->json(['basket' => $basketData], 200);
}

public function add(Request $request): JsonResponse
{
    /* ───── 1.  ВАЛИДАЦИЯ ───── */
    $data = $request->validate([
        'product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],
        'organization_id'    => ['required','uuid','exists:organizations,id'],   // ➊ NEW
        'source_table'       => ['required','string','in:sales,price_offer_items,favorites'],
        'source_table_id'    => ['required','uuid'],
        'amount'             => ['sometimes','numeric','gt:0'],
        'quantity'           => ['sometimes','numeric','gt:0'],
        'price'              => ['required','numeric','gte:0'],
        'total_sum'          => ['sometimes','numeric','gte:0'],                // ➋ переименовали
        'unit_measurement'   => ['required','string','max:255'],
    ]);

    /* ───── 2.  НОРМАЛИЗАЦИЯ ───── */
    $data['quantity']  = $data['quantity'] ?? $data['amount'] ?? 1;
    $data['total_sum'] = $data['total_sum'] ?? $data['quantity'] * $data['price'];

    $userId = Auth::id();

    /* ───── 3.  «Ключ» записи — теперь ещё и organization_id ───── */
    $where = [
        'id_client_request'  => $userId,
        'product_subcard_id' => $data['product_subcard_id'],
        'organization_id'    => $data['organization_id'],                      // ➌ NEW
        'source_table'       => $data['source_table'],
        'source_table_id'    => $data['source_table_id'],
    ];

    /* ───── 4.  Поля для обновления / создания ───── */
    $payload = [
        'price'            => $data['price'],
        'unit_measurement' => $data['unit_measurement'],
        'total_sum'        => DB::raw('COALESCE(total_sum,0) + '.$data['total_sum']),
        'quantity'         => DB::raw('COALESCE(quantity,0) + '.$data['quantity']),
    ];

    try {
        Basket::updateOrCreate($where, $payload);
        return response()->json(['success' => true]);
    } catch (\Throwable $e) {
        Log::error('Basket add error', ['msg' => $e->getMessage()]);
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


// app/Http/Controllers/BasketController.php
public function changeQuantity(Request $request): JsonResponse
{
    $rules = [
        'product_subcard_id' => ['required','uuid','exists:baskets,product_subcard_id'],
        'source_table'       => ['required','string','in:sales,price_offer_items,favorites'],
        'source_table_id'    => ['required','uuid'],
        'type'               => ['required','string','in:increment,decrement'],
        'step'               => ['sometimes','integer','min:1'], // ↓/↑ на сколько (по-умолчанию 1)
    ];

    try {
        $data = $request->validate($rules);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }

    $step   = $data['step'] ?? 1;
    $delta  = $data['type'] === 'decrement' ? -$step : $step;
    $userId = Auth::id();

    /** берём строку корзины пользователя */
    $item = Basket::where([
                'id_client_request'  => $userId,
                'product_subcard_id' => $data['product_subcard_id'],
                'source_table'       => $data['source_table'],
                'source_table_id'    => $data['source_table_id'],
            ])->first();

    if (!$item) {
        return response()->json(['error' => 'Basket item not found'], 404);
    }

    /** считаем, какое число получится после изменения */
    $newQty = $item->quantity + $delta;

    // ───────────────────────────────────────────────────────────────────
    //  ЛОГИКА: меньше 1 не даём, но и запись не удаляем
    // ───────────────────────────────────────────────────────────────────
    if ($newQty < 1) {
        $newQty = 1;             // «зажимаем» минимальное значение
    }

    $item->quantity = $newQty;
    $item->save();

    return response()->json([
        'success'  => true,
        'quantity' => $item->quantity,
    ]);
}



    /**
     * Remove a product from the basket.
     */
    public function destroy(int $id): JsonResponse
{
    $userId = Auth::id();

    // ищем только в корзине текущего пользователя
    $basketItem = Basket::where('id_client_request', $userId)
                        ->find($id);

    if (!$basketItem) {
        return response()->json([
            'success' => false,
            'error'   => 'Product not found in basket'
        ], 404);
    }

    $basketItem->delete();

    return response()->json([
        'success' => true,
        'message' => 'Product removed from basket'
    ]);
}
    /**
     * Clear all items from the user's basket.
     */
    public function clear()
    {
        $userId = Auth::id();
        Basket::where('id_client_request', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Basket cleared'
        ], 200);
    }

    /**
     * Place an order from the basket.
     */

     public function placeOrder(Request $request): JsonResponse
{
    /* ───── 1.  ВАЛИДАЦИЯ ───── */
    $request->validate([
        'address' => ['required','string','max:255'],
    ]);

    $user    = Auth::user();
    $address = $request->input('address');

    /* ───── 2.  Корзина пользователя ───── */
    $basket = Basket::where('id_client_request', $user->id)->get();
    if ($basket->isEmpty()) {
        return response()->json(['error' => 'Your basket is empty'], 400);
    }

    /* ⬇ убедимся, что в корзине нет товаров из разных организаций */
    if ($basket->pluck('organization_id')->unique()->count() !== 1) {
        return response()->json([
            'success' => false,
            'error'   => 'Корзина содержит товары из разных организаций. Оформите их отдельными заказами.',
        ], 400);
    }
    $organizationId = $basket->first()->organization_id;                       // ➍ NEW

    /* ───── 3.  Статус «на фасовке» ───── */
    $waitingStatusId = StatusDoc::where('name', 'на фасовке')->value('id');
    if (!$waitingStatusId) {
        return response()->json([
            'success' => false,
            'error'   => 'Статус «на фасовке» не найден в таблице status_docs',
        ], 500);
    }

    /* ───── 4.  ТРАНЗАКЦИЯ ───── */
    DB::beginTransaction();
    try {
        /* 4-A. Шапка заказа */
        $order = Order::create([
            'id'              => (string) Str::uuid(),
            'user_id'         => $user->id,
            'organization_id' => $organizationId,                              // ➎ NEW
            'status_id'       => $waitingStatusId,
            'address'         => $address,
            'total_sum'       => 0, // заполним ниже
        ]);

        /* 4-B. Строки заказа + итог */
        $total = 0;
        foreach ($basket as $row) {
            $lineSum = $row->total_sum ?? $row->price * $row->quantity;

            $order->orderItems()->create([
                'product_subcard_id' => $row->product_subcard_id,
                'source_table'       => $row->source_table,
                'source_table_id'    => $row->source_table_id,
                'quantity'           => $row->quantity,
                'price'              => $row->price,
                'unit_measurement'   => $row->unit_measurement,
                'total_sum'          => $lineSum,
            ]);

            $total += $lineSum;
        }

        /* 4-C. Итог по заказу */
        $order->update(['total_sum' => $total]);

        /* 4-D. Очищаем корзину */
        Basket::where('id_client_request', $user->id)->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'order'   => $order->load('orderItems'),
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Place order error', ['msg' => $e->getMessage()]);

        return response()->json([
            'success' => false,
            'error'   => 'Could not create order: '.$e->getMessage(),
        ], 500);
    }
}
    /**
     * Get the details of an order, including product subcards.
     */
    public function getOrderDetails($orderId)
    {
        $order = Order::with('orderItems.productSubCard.productCard')->findOrFail($orderId);
        return response()->json(['success' => true, 'data' => $order]);
    }

    /**
     * Update the status of an order.
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status'  => 'required|in:pending,processing,packed,shipped,delivered,canceled',
            'remarks' => 'nullable|string',
        ]);

        $order = Order::findOrFail($orderId);
        $order->status = $validated['status'];

        // Update timestamps based on status
        if ($validated['status'] === 'shipped') {
            $order->shipped_at = now();
        }
        if ($validated['status'] === 'delivered') {
            $order->delivered_at = now();
        }

        $order->save();

        // Log the status change (optional)
        Order::create([
            'order_id' => $order->id,
            'status'   => $validated['status'],
            'remarks'  => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'order'   => $order
        ]);
    }
}
