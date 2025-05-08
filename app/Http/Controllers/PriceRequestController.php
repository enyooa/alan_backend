<?php
/*  app/Http/Controllers/PriceRequestController.php  */

namespace App\Http\Controllers;

use App\Models\{
    PriceOfferOrder,
    PriceOfferItem,
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
    /* ─────────────────────── 1. LIST ─────────────────────── */
    public function index(Request $request): JsonResponse
    {
        $orgId    = $request->user()->organization_id;
        $clientId = $this->clean($request->query('client_id'));
        $date     = $this->clean($request->query('date'));

        $orders = PriceOfferOrder::with([
                'client:id,first_name,last_name',
                'address',
                'warehouse',
                'items.product',
                'items.unit',
            ])
            ->where('organization_id', $orgId)
            ->when($clientId,
                   fn ($q) => $q->where('client_id', $clientId))
            ->when($date, function ($q) use ($date) {
                  $parsed = Carbon::parse($date)->toDateString();
                  $q->whereDate('start_date', '<=', $parsed)
                    ->whereDate('end_date',   '>=', $parsed);
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        return response()->json($orders);
    }

    /* ─────────────────────── 2. SHOW ─────────────────────── */
    public function show(PriceOfferOrder $order): JsonResponse
    {
        return response()->json(
            $order->load([
                'client:id,first_name,last_name',
                'address',
                'warehouse',
                'items.product',
                'items.unit',
            ])
        );
    }

    /* ─────────────────────── 3. STORE ────────────────────── */
    public function store(Request $request): JsonResponse
    {
        Log::info($request->all());

        try   { $data = $this->validatePayload($request); }
        catch (ValidationException $e) {
            return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
        }

        $data['organization_id'] = $request->user()->organization_id;

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

    /* ─────────────────────── 4. UPDATE ───────────────────── */
    public function update(Request $request, PriceOfferOrder $order): JsonResponse
    {
        try   { $data = $this->validatePayload($request, $order->id); }
        catch (ValidationException $e) {
            return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $order->items()->delete();

            $order->update([
                'client_id'    => $data['client_id']  ?? $order->client_id,
                'address_id'   => $data['address_id'] ?? $order->address_id,
                'warehouse_id' => $data['warehouse_id'],
                'start_date'   => Carbon::parse($data['start_date'])->toDateString(),
                'end_date'     => Carbon::parse($data['end_date'])->toDateString(),
                'totalsum'     => 0,
            ]);

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

    /* ─────────────────────── 5. DELETE ───────────────────── */
    public function destroy(PriceOfferOrder $order): JsonResponse
    {
        DB::beginTransaction();
        try {
            $order->items()->delete();
            $order->delete();
            DB::commit();
            return response()->json(['success'=>true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail($e);
        }
    }

    /* ═════════════════════ HELPERS ════════════════════════ */

    protected function clean($v) {
        if ($v === null) return null;
        $t = trim((string)$v);
        return ($t==='' || in_array(strtolower($t),['null','undefined'],true)) ? null : $t;
    }

    /** Валидация (create / update) */
    protected function validatePayload(Request $r, ?string $orderId = null): array
    {
        $rules = [
            'warehouse_id'  => ['required','uuid','exists:warehouses,id'],
            'start_date'    => ['required','date'],
            'end_date'      => ['required','date','after_or_equal:start_date'],

            'products'                                      => ['required','array','min:1'],
            'products.*.product.product_subcard_id'         => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.name'                          => ['required','string','max:255'],   // ← только name
            'products.*.price'                              => ['required','numeric','gt:0'],
        ];

        $rules['client_id']  = $orderId ? ['sometimes','uuid','exists:users,id']
                                        : ['required','uuid','exists:users,id'];
        $rules['address_id'] = $orderId ? ['sometimes','uuid','exists:addresses,id']
                                        : ['required','uuid','exists:addresses,id'];

        $qtyRuleCreate = ['nullable','numeric','gt:0'];
        $qtyRuleUpdate = ['nullable','numeric','gte:0'];
        $rules['products.*.qty']     = $orderId ? $qtyRuleUpdate : $qtyRuleCreate;
        $rules['products.*.qtyTare'] = $orderId ? $qtyRuleUpdate : $qtyRuleCreate;

        return $r->validate($rules);
    }

    /** Создание / обновление + строки */
    protected function insertOrderWithItems(array $data,
                                            ?PriceOfferOrder $existing = null): PriceOfferOrder
    {
        /* 1. Header */
        $order = $existing ?? PriceOfferOrder::create([
            'client_id'       => $data['client_id'],
            'address_id'      => $data['address_id'],
            'warehouse_id'    => $data['warehouse_id'],
            'organization_id' => $data['organization_id'],
            'start_date'      => Carbon::parse($data['start_date'])->toDateString(),
            'end_date'        => Carbon::parse($data['end_date'])->toDateString(),
            'totalsum'        => 0,
        ]);

        /* 2. Lines */
        $sum   = 0;
        $items = [];
        $whId  = $data['warehouse_id'];

        foreach ($data['products'] as $row) {

            $prodId   = $row['product']['product_subcard_id'];  // UUID
            $unitName = $row['unit']['name'];                   // строка «коробка»
            $qtyRaw   = $row['qtyTare'] ?? $row['qty'] ?? 0;
            $qty      = (float)($qtyRaw === '' ? 0 : $qtyRaw);
            $price    = (float)$row['price'];

            /* остаток на складе */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $prodId,
                        'unit_measurement'   => $unitName,
                     ])->first();

            if (!$stock || $stock->quantity < $qty) {
                throw new \Exception("Не хватает товара id={$prodId} ({$unitName}) на складе #{$whId}");
            }

            $lineSum = $qty * $price;
            $sum    += $lineSum;

            $items[] = new PriceOfferItem([
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
                'amount'             => $qty,
                'price'              => $price,
            ]);
        }

        $order->items()->saveMany($items);
        $order->update(['totalsum' => $sum]);

        return $order;
    }

    protected function fail(\Throwable $e): JsonResponse
    {
        Log::error('PriceOffer error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}
