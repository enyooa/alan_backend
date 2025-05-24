<?php
/* app/Http/Controllers/PriceRequestController.php */

namespace App\Http\Controllers;

use App\Models\{
    PriceOfferOrder,
    PriceOfferItem
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PriceRequestController extends Controller
{
    /* ───────────────── 1. LIST ───────────────── */
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



    /* ───────────────── 2. SHOW ───────────────── */
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

    /* ───────────────── 3. STORE ──────────────── */
    public function store(Request $request): JsonResponse
    {
        try { $data = $this->validatePayload($request); }
        catch (ValidationException $e) {
            return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
        }

        // organization_id может быть null, если user не аутентифицирован
        $data['organization_id'] = optional($request->user())->organization_id;   // ← FIX safe

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

    /* ───────────────── 4. UPDATE ─────────────── */
    public function update(Request $request, PriceOfferOrder $order): JsonResponse
    {
        Log::info($request->all());
        try { $data = $this->validatePayload($request, $order->id); }
        catch (ValidationException $e) {
            return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
        }

        // organisation остаётся как у существующего ордера
        $data['organization_id'] = $order->organization_id;

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

    /* ───────────────── 5. DELETE ─────────────── */
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

    /* ═══════════════════ HELPERS ═══════════════ */

    protected function clean($v) {
        if ($v === null) return null;
        $t = trim((string)$v);
        return ($t==='' || in_array(strtolower($t),['null','undefined'],true)) ? null : $t;
    }

    protected function validatePayload(Request $r, ?string $orderId = null): array
    {
        if ($r->has('price_offer_items')) {
        $r->merge(['products' => $r->input('price_offer_items')]);
    }
        $rules = [
            'warehouse_id'  => ['sometimes','uuid','exists:warehouses,id'], // ← при редактировании «может быть»
        'start_date'    => ['required','date'],
        'end_date'      => ['required','date','after_or_equal:start_date'],
            'products'                                      => ['required','array','min:1'],
            'products.*.product.product_subcard_id'         => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.name'                          => ['required','string','max:255'],
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

    protected function insertOrderWithItems(array $data,
                                            ?PriceOfferOrder $existing = null): PriceOfferOrder
    {
        $order = $existing ?? PriceOfferOrder::create([
            'client_id'       => $data['client_id'],
            'address_id'      => $data['address_id'],
            'warehouse_id'    => $data['warehouse_id'],
            'organization_id' => $data['organization_id'],               // ← FIX
            'start_date'      => Carbon::parse($data['start_date'])->toDateString(),
            'end_date'        => Carbon::parse($data['end_date'])->toDateString(),
            'totalsum'        => 0,
        ]);

        $sum   = 0;
        $items = [];

        foreach ($data['products'] as $row) {
            $qty   = (float)($row['qtyTare'] ?? $row['qty'] ?? 0);
            $price = (float)$row['price'];

            $items[] = new PriceOfferItem([
                'product_subcard_id' => $row['product']['product_subcard_id'],
                'unit_measurement'   => $row['unit']['name'],
                'amount'             => $qty,
                'price'              => $price,
            ]);

            $sum += $qty * $price;
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
