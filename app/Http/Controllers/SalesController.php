<?php

namespace App\Http\Controllers;

use App\Models\ReferenceItem;
use Illuminate\Http\Request;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
class SalesController extends Controller
{
    public function getSalesWithDetails(): JsonResponse
{
    /* product ⬅︎ReferenceItem   card ⬅︎его родительская карточка
       (если card_id заполнен)   */
    $sales = Sale::with(['product.card'])->get();

    return response()->json($sales);
}





    public function store(Request $request)
    {
        // Log the raw incoming data for debugging
        Log::info('Received sale data:', $request->all());

        // Validate the incoming request
        $validated = $request->validate([
            'product_subcard_id' => 'required|integer|exists:product_subcards,id', // Ensure the subcard exists
            'unit_measurement' => 'nullable|string|max:255',
            'amount' => 'required|integer|min:1', // Ensure amount is positive
            'price' => 'required|integer|min:1', // Ensure price is positive
            'totalsum' => $request->amount * $request->price,
        ]);

        // Create the sale record in the database
        $sale = Sale::create($validated);

        // Respond with success and the created sale data
        return response()->json([
            'message' => 'Sale successfully created!',
            'data' => $sale,
        ], 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        Log::info('Bulk-sale payload', $request->all());

        $data = $request->validate([
            'products'                                => ['required','array','min:1'],
            'products.*.product.product_subcard_id'   => ['required','integer'],
            'products.*.unit.id'                      => ['required','integer','exists:reference_items,id'],
            'products.*.qty'                          => ['nullable','numeric','gt:0'],
            'products.*.amount'                       => ['nullable','numeric','gt:0'],
            'products.*.price'                        => ['required','numeric','gt:0'],
        ]);

        foreach ($data['products'] as $row) {
            $prodId   = (int) $row['product']['product_subcard_id'];
            $unitId   = (int) $row['unit']['id'];
            $unitName = ReferenceItem::find($unitId)->name;
            $qty      = $row['qty'] ?? $row['amount'] ?? 1;

            Sale::create([
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
                'amount'             => $qty,
                'price'              => $row['price'],
            ]);
        }

        return response()->json(['message' => 'Продажи успешно созданы!'], 201);
    }
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'product.product_subcard_id' => ['sometimes','integer'],
            'unit.id'                    => ['sometimes','integer','exists:reference_items,id'],
            'qty'                        => ['sometimes','numeric','gt:0'],
            'amount'                     => ['sometimes','numeric','gt:0'],
            'price'                      => ['sometimes','numeric','gt:0'],
        ]);

        /* ── нормализация: если пришёл unit.id → переводим в unit_measurement-name ── */
        if (isset($data['unit']['id'])) {
            $data['unit_measurement'] = ReferenceItem::find($data['unit']['id'])->name;
        }
        if (isset($data['product']['product_subcard_id'])) {
            $data['product_subcard_id'] = $data['product']['product_subcard_id'];
        }
        $data['amount'] = $data['qty'] ?? $data['amount'] ?? $sale->amount;

        /* удаляем вложенные массивы, чтобы не ломать mass-assign */
        unset($data['product'], $data['unit'], $data['qty']);

        $sale->update($data);

        return response()->json(['message' => 'Sale updated successfully']);
    }

public function destroy($id)
{
    Sale::destroy($id);

    return response()->json(['message' => 'Sale deleted successfully'], 200);
}



}
