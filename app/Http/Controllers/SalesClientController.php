<?php

namespace App\Http\Controllers;

use App\Models\{ Sale, SaleItem, Unit_measurement, WarehouseItem };
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SalesClientController extends Controller
{
    /*══════════════════════ 1. LIST ══════════════════════*/
    public function getSalesWithDetails(Request $request): JsonResponse
{
    // $orgId = $request->user()->organization_id;   // ← фильтр по организации (при необходимости)

    $query = Sale::query();

    /* ——— фильтр по дате ——— */
    if ($request->filled('date')) {
        try {
            $date = Carbon::parse($request->query('date'))->toDateString();
            $query->whereDate('sale_date', $date);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Неверный формат date (YYYY-MM-DD)',
            ], 422);
        }
    }

    /* ——— поиск по названию товара/карточки ——— */
    if ($request->filled('search')) {
        $needle = mb_strtolower(trim($request->query('search')), 'UTF-8');

        /* (1) берём только те продажи, где есть совпавшая строка */
        $query->whereHas('items.product', function ($q) use ($needle) {
            $q->whereRaw('LOWER(product_sub_cards.name) LIKE ?', ["%{$needle}%"]);
        });

        /* (2) грузим совпавшие строки + организацию */
        $query->with([
            'organization',                                         // ← ДОБАВЛЕНО
            'items' => function ($q) use ($needle) {
                $q->whereHas('product', function ($p) use ($needle) {
                    $p->whereRaw('LOWER(product_sub_cards.name) LIKE ?', ["%{$needle}%"]);
                });
            },
            'items.product.productCard',
        ]);
    } else {
        // стандартная подгрузка: организация + товары
        $query->with([
            'organization',                                         // ← ДОБАВЛЕНО
            'items.product.productCard',
        ]);
    }

    /* ——— финальная выборка + фиксация URL-ов ——— */
    $sales = $query->get();

    foreach ($sales as $sale) {
        // 1) организация: пример обработки логотипа / другого файла
        if ($org = $sale->organization) {
            $org->logo_url = $org->logo_path
                ? asset('storage/' . ltrim($org->logo_path, '/'))
                : null;
        }

        // 2) карточки товаров — как и было
        foreach ($sale->items as $item) {
            if ($pc = optional($item->product)->productCard) {
                $pc->photo_product_url = $pc->photo_product
                    ? asset('storage/' . ltrim($pc->photo_product, '/'))
                    : null;
            }
        }
    }

    return response()->json($sales);
}

    /*══════════════════════ 2. STORE (one line) ══════════════════════*/
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_subcard_id' => ['required','uuid','exists:product_sub_cards,id'],
            'unit_measurement'   => ['nullable','string','max:255'],
            'amount'             => ['required','numeric','gt:0'],
            'price'              => ['required','numeric','gt:0'],
        ]);

        $sale = Sale::create([
            'id'              => Str::uuid(),
            'organization_id' => $request->user()->organization_id,      // 👈
            'sale_date'       => now()->toDateString(),
            'total_sum'       => $data['amount'] * $data['price'],
        ]);

        $sale->items()->create([
            'id'                 => Str::uuid(),
            'product_subcard_id' => $data['product_subcard_id'],
            'unit_measurement'   => $data['unit_measurement'],
            'amount'             => $data['amount'],
            'price'              => $data['price'],
            'total_sum'          => $data['amount'] * $data['price'],
        ]);

        return response()->json([
            'message' => 'Sale created',
            'data'    => $sale->load('items'),
        ], 201);
    }

    /*══════════════════════ 3. BULK STORE ══════════════════════*/
    public function bulkStore(Request $request): JsonResponse
    {
        Log::info($request->all());
        $lines = collect($request->input('products', []));

        if ($lines->isEmpty()) {
            return response()->json(['error' => 'products array required'], 422);
        }

        /* header */
        $sale = Sale::create([
            'id'              => Str::uuid(),
            'organization_id' => $request->user()->organization_id,      // 👈
            'client_id'       => $request->input('client_id'),
            'warehouse_id'    => $request->input('warehouse_id'),
            'sale_date'       => Carbon::parse($request->input('sale_date', now()))->toDateString(),
            'total_sum'       => 0,
        ]);

        $sum = 0;
        $items = [];

        foreach ($lines as $row) {
            $qtyRaw = $row['amount'] ?? $row['qtyTare'] ?? $row['qty'] ?? 1;
            $qty    = (float) ($qtyRaw === '' ? 1 : $qtyRaw);
            $price  = (float) ($row['price'] ?? 0);
            $total  = $qty * $price;

            $items[] = new SaleItem([
                'id'                 => Str::uuid(),
                'product_subcard_id' => data_get($row,'product.product_subcard_id'),
                'unit_measurement'   => data_get($row,'unit.name'),
                'amount'             => $qty,
                'price'              => $price,
                'total_sum'          => $total,
            ]);

            $sum += $total;
        }

        $sale->items()->saveMany($items);
        $sale->update(['total_sum' => $sum]);

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'total'   => $sum,
        ], 201);
    }

    /*══════════════════════ 4. UPDATE ══════════════════════*/
    public function update(Request $request, Sale $sale): JsonResponse
    {
        abort_if($sale->organization_id !== $request->user()->organization_id, 404);

        $data = $request->validate([
            'client_id'    => ['sometimes','uuid','exists:users,id'],
            'warehouse_id' => ['sometimes','uuid','exists:warehouses,id'],
            'sale_date'    => ['sometimes','date'],

            'products'                                      => ['required','array','min:1'],
            'products.*.product.product_subcard_id'         => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.name'                          => ['required','string','max:255'],
            'products.*.price'                              => ['required','numeric','gte:0'],
            'products.*.amount'   => ['nullable','numeric','gt:0'],
            'products.*.qtyTare'  => ['nullable','numeric','gt:0'],
            'products.*.qty'      => ['nullable','numeric','gt:0'],
        ]);

        DB::beginTransaction();
        try {
            /* header */
            $sale->update([
                'client_id'    => $data['client_id']    ?? $sale->client_id,
                'warehouse_id' => $data['warehouse_id'] ?? $sale->warehouse_id,
                'sale_date'    => $data['sale_date']    ?? $sale->sale_date,
            ]);

            $sale->items()->delete();

            $total = 0;
            $items = [];

            foreach ($data['products'] as $row) {
                $qtyRaw = $row['amount'] ?? $row['qtyTare'] ?? $row['qty'] ?? 1;
                $qty    = (float)($qtyRaw === '' ? 1 : $qtyRaw);
                $price  = (float)$row['price'];
                $sum    = $qty * $price;

                $items[] = [
                    'id'                 => Str::uuid(),
                    'product_subcard_id' => data_get($row,'product.product_subcard_id'),
                    'unit_measurement'   => data_get($row,'unit.name'),
                    'amount'             => $qty,
                    'price'              => $price,
                    'total_sum'          => $sum,
                ];

                $total += $sum;
            }

            $sale->items()->createMany($items);
            $sale->update(['total_sum' => $total]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Продажа обновлена',
                'sale_id' => $sale->id,
                'total'   => $total,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('updateSale error', ['msg'=>$e->getMessage()]);

            return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
        }
    }

    /*══════════════════════ 5. DESTROY ══════════════════════*/
    public function destroy(Sale $sale): JsonResponse
    {
        abort_if($sale->organization_id !== request()->user()->organization_id, 404);

        DB::beginTransaction();
        try {
            $sale->items()->delete();
            $sale->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Продажа и все её позиции удалены',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('destroySale error', ['msg'=>$e->getMessage()]);

            return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
        }
    }

    /*══════════════ helper for LIKE search (если понадобится) ═════════════*/
    private function applyProductSearch($builder, string $needle): void
    {
        $builder->whereRaw('LOWER(product_sub_cards.name) LIKE ?', ["%{$needle}%"])
                ->orWhereHas('productCard', function ($q) use ($needle) {
                    $q->whereRaw('LOWER(name_of_products) LIKE ?', ["%{$needle}%"])
                      ->orWhereRaw('LOWER(description)      LIKE ?', ["%{$needle}%"])
                      ->orWhereRaw('LOWER(country)          LIKE ?', ["%{$needle}%"])
                      ->orWhereRaw('LOWER(`type`)           LIKE ?', ["%{$needle}%"]);
                });
    }
}
