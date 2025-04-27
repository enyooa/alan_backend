<?php
/*  app/Http/Controllers/PriceRequestController.php  */

namespace App\Http\Controllers;

use App\Models\{
    PriceOfferOrder,
    PriceOfferItem,
    ReferenceItem,
    WarehouseItem
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PriceRequestController extends Controller
{
    /*────────────────────────────── 1. LIST ──────────────────────────────*/
    /*──────────────────────── 1. LIST c фильтрами ───────────────────────*/
    public function index(Request $request): JsonResponse
{
    // «очищаем» вход: превращаем 'null', '',  'undefined' → null
    $clientId = $this->clean($request->query('client_id'));
    $date     = $this->clean($request->query('date'));

    $orders = PriceOfferOrder::with([
            'client:id,first_name,last_name',
            'address',
            'warehouse',
            'items.product.reference',
            'items.unitRef',
        ])

        /* фильтр по client_id (если задан) */
        ->when($clientId !== null, function ($q) use ($clientId) {
            $q->where('client_id', (int) $clientId);
        })

        /* фильтр по дате (если задана валидная дата) */
        ->when($date !== null, function ($q) use ($date) {
            // Carbon бросит исключение только если $date действительно не дата
            $parsed = \Carbon\Carbon::parse($date)->toDateString();
            $q->whereDate('start_date', '<=', $parsed)
              ->whereDate('end_date',   '>=', $parsed);
        })

        ->orderByDesc('created_at')
        ->paginate(25)
        ->appends($request->query());   // чтобы пагинация сохраняла query-параметры

    return response()->json($orders);
}

/**
 * Превращает строки 'null', '', 'undefined', 0-символьные пробелы → null
 * оставшиеся значения возвращает как есть.
 */
protected function clean($value)
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim((string) $value);

    return ($trimmed === '' || strtolower($trimmed) === 'null' || strtolower($trimmed) === 'undefined')
           ? null
           : $trimmed;
}



    /*────────────────────────────── 2. SHOW ──────────────────────────────*/
    public function show(PriceOfferOrder $order): JsonResponse
    {
        return response()->json(
            $order->load([
                'client:id,first_name,last_name',
                'address',
                'warehouse',
                'items.product.reference',
                'items.unitRef',
            ])
        );
    }

    /*────────────────────────────── 3. STORE ─────────────────────────────*/
    public function store(Request $request): JsonResponse
    {
        Log::info($request->all());

        try {
            $data = $this->validatePayload($request);   // create-mode
        } catch (ValidationException $e) {
            return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $order = $this->insertOrderWithItems($data);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ценовое предложение создано',
                'offer'   => $order->load(['warehouse','items']),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*────────────────────────────── 4. UPDATE ────────────────────────────*/
    public function update(Request $request, PriceOfferOrder $order): JsonResponse
    {
        Log::info($request->all());

        try {
            $data = $this->validatePayload($request, $order->id);  // update-mode
        } catch (ValidationException $e) {
            return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            /* 1. убираем старые строки */
            $order->items()->delete();

            /* 2. обновляем «шапку» */
            $order->update([
                'client_id'    => $data['client_id']  ?? $order->client_id,
                'address_id'   => $data['address_id'] ?? $order->address_id,
                'warehouse_id' => $data['warehouse_id'],
                'start_date'   => Carbon::parse($data['start_date'])->toDateString(),
                'end_date'     => Carbon::parse($data['end_date'])->toDateString(),
                'totalsum'     => 0,
            ]);

            /* 3. вставляем новые строки */
            $order = $this->insertOrderWithItems($data, $order);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ценовое предложение обновлено',
                'offer'   => $order->load(['warehouse','items']),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*────────────────────────────── 5. DELETE ────────────────────────────*/
    public function destroy(PriceOfferOrder $order): JsonResponse
    {
        DB::beginTransaction();
        try {
            $order->items()->delete();
            $order->delete();
            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /*══════════════════════════════ HELPERS ══════════════════════════════*/

    /**
     * Универсальная валидация payload'а.
     * При $orderId === null — режим create, иначе update (некоторые поля становятся optional).
     */
    protected function validatePayload(Request $r, ?int $orderId = null): array
    {
        $rules = [
            'warehouse_id'  => ['required','integer','exists:warehouses,id'],

            'start_date'    => ['required','date'],
            'end_date'      => ['required','date','after_or_equal:start_date'],

            'products'                                => ['required','array','min:1'],
            'products.*.product.product_subcard_id'   => ['required','integer','exists:reference_items,id'],
            'products.*.unit.id'                      => ['required','integer','exists:reference_items,id'],
            'products.*.price'                        => ['required','numeric','gt:0'],
        ];

        // client / address: обязательны при создании, факультативны при обновлении
        $rules['client_id']  = $orderId
            ? ['sometimes','integer','exists:users,id']
            : ['required','integer','exists:users,id'];

        $rules['address_id'] = $orderId
            ? ['sometimes','integer','exists:addresses,id']
            : ['required','integer','exists:addresses,id'];

        // qty/qtyTare: >0 при создании, ≥0 при обновлении
        $qtyRuleCreate  = ['nullable','numeric','gt:0'];
        $qtyRuleUpdate  = ['nullable','numeric','gte:0'];
        $rules['products.*.qty']     = $orderId ? $qtyRuleUpdate : $qtyRuleCreate;
        $rules['products.*.qtyTare'] = $orderId ? $qtyRuleUpdate : $qtyRuleCreate;

        return $r->validate($rules);
    }

    /**
     * Создаёт новый заказ или добавляет строки к существующему.
     *
     * @param  array                    $data
     * @param  PriceOfferOrder|null     $existing
     * @return PriceOfferOrder
     */
    protected function insertOrderWithItems(array $data,
                                            ?PriceOfferOrder $existing = null): PriceOfferOrder
    {
        /* 1. Шапка */
        $order = $existing ?? PriceOfferOrder::create([
            'client_id'    => $data['client_id'],
            'address_id'   => $data['address_id'],
            'warehouse_id' => $data['warehouse_id'],
            'start_date'   => Carbon::parse($data['start_date'])->toDateString(),
            'end_date'     => Carbon::parse($data['end_date'])->toDateString(),
            'totalsum'     => 0,
        ]);

        /* 2. Строки */
        $sumTotal = 0;
        $items    = [];
        $whId     = $data['warehouse_id'];

        foreach ($data['products'] as $row) {
            $prodId   = (int) $row['product']['product_subcard_id'];
            $unitId   = (int) $row['unit']['id'];
            $unitName = ReferenceItem::findOrFail($unitId)->name;

            $qtyRaw   = $row['qtyTare'] ?? $row['qty'] ?? 0;
            $qty      = (float) ($qtyRaw === '' ? 0 : $qtyRaw);
            $price    = (float) $row['price'];

            /* Проверка остатка */
            $stock = WarehouseItem::where([
                         'warehouse_id'       => $whId,
                         'product_subcard_id' => $prodId,
                         'unit_measurement'   => $unitName,
                     ])->first()
                  ?? WarehouseItem::where([
                         'warehouse_id'       => $whId,
                         'product_subcard_id' => $prodId,
                         'unit_measurement'   => $unitId,
                     ])->first();

            if (!$stock || $stock->quantity < $qty) {
                throw new \Exception(
                    "Не хватает товара id={$prodId} ({$unitName}) на складе #{$whId}"
                );
            }

            $lineSum  = $qty * $price;
            $sumTotal += $lineSum;

            $items[] = new PriceOfferItem([
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,   // сохраняем название!
                'amount'             => $qty,
                'price'              => $price,
            ]);
        }

        $order->items()->saveMany($items);
        $order->update(['totalsum' => $sumTotal]);

        return $order;
    }

    /** Унифицированный ответ-ошибка */
    protected function fail(\Throwable $e): JsonResponse
    {
        Log::error('PriceOffer error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}
