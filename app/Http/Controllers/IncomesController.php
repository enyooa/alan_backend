<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Expense;
use App\Models\Unit_measurement;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class IncomesController extends Controller
{



    /**
 * POST /api/income-products
 * Сохранить новый документ-«Приход» вместе
 *   – товарными позициями
 *   – складскими остатками
 *   – дополнительными расходами (expense_name_id)
 */
public function storeIncomes(Request $request): JsonResponse
{
    Log::info($request);
    /* ─────────────────────── 1. ВАЛИДАЦИЯ ─────────────────────── */
    $rules = [

        /* header */
        'providerId'            => ['nullable','uuid','exists:providers,id'],
        'assigned_warehouse_id' => ['required','uuid','exists:warehouses,id'],
        'docDate'               => ['sometimes','date'],

        /* products */
        'products'                                => ['required','array','min:1'],
        'products.*.product.id'                   => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.unit.id'                      => ['required','uuid','exists:unit_measurements,id'],
        'products.*.qtyTare'                      => ['nullable','numeric'],
        'products.*.brutto'                       => ['nullable','numeric'],
        'products.*.netto'                        => ['nullable','numeric'],
        'products.*.price'                        => ['required','numeric'],
        'products.*.total_sum'                    => ['required','numeric'],
        'products.*.additional_expenses'          => ['nullable','numeric'],
        'products.*.cost_price'                   => ['nullable','numeric'],

        /* expenses — допускаем 2 формата id */
        'expenses'                                => ['sometimes','array'],

        // старый: { "name": { "id": … } }
        'expenses.*.name.id'                      => [
            'nullable','uuid','exists:expense_names,id',
            'required_without:expenses.*.expense_name_id'
        ],

        // новый:  expense_name_id
        'expenses.*.expense_name_id'              => [
            'nullable','uuid','exists:expense_names,id',
            'required_without:expenses.*.name.id'
        ],

        'expenses.*.provider.id'                  => ['nullable','uuid','exists:providers,id'],
        'expenses.*.amount'                       => ['required_with:expenses','numeric'],
    ];

    $data = $request->validate($rules);

    /* ─────────────────────── 2. ШОРТКАТЫ ─────────────────────── */
    $orgId      = $request->user()->organization_id;
    $whId       = $data['assigned_warehouse_id'];
    $providerId = $data['providerId'] ?? null;
    $docDate    = Carbon::parse($data['docDate'] ?? now())->toDateString();

    $products = collect($data['products']);
    $expenses = collect($data['expenses'] ?? []);

    /* ─────────────────────── 3. ТРАНЗАКЦИЯ ───────────────────── */
    DB::beginTransaction();
    try {
        /* 3-A. документ-«шапка» */
        $doc = Document::create([
            'organization_id'   => $orgId,
            'document_type_id'  => DocumentType::whereCode('income')->firstOrFail()->id,
            'status'            => '+',
            'provider_id'       => $providerId,
            'to_warehouse_id'   => $whId,
            'document_date'     => $docDate,
            'comments'          => $request->input('comments',''),
        ]);

        /* 3-B. товарные строки + склад */
        foreach ($products as $row) {

            $prodId   = data_get($row,'product.id');
            $unitName = Unit_measurement::findOrFail(data_get($row,'unit.id'))->name;

            $qty = $row['qtyTare'] !== null
                 ? (float) $row['qtyTare']
                 : (float) ($row['netto'] ?? 0);

            // document_items
            DocumentItem::create([
                'document_id'        => $doc->id,
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
                'quantity'           => $qty,
                'brutto'             => (float) ($row['brutto'] ?? 0),
                'netto'              => (float) ($row['netto']  ?? 0),
                'price'              => (float) $row['price'],
                'total_sum'          => (float) $row['total_sum'],
                'additional_expenses'=> (float) ($row['additional_expenses'] ?? 0),
                'cost_price'         => (float) ($row['cost_price']          ?? 0),
                'net_unit_weight'    => $qty > 0
                                        ? round(($row['netto'] ?? 0) / $qty, 4)
                                        : 0,
            ]);

            // warehouse_items
            $whItem = WarehouseItem::firstOrNew([
                'document_id' =>$doc->id,
                'warehouse_id'       => $whId,
                'product_subcard_id' => $prodId,
                'unit_measurement'   => $unitName,
            ]);

            $whItem->quantity            += $qty;
            $whItem->brutto              += (float) ($row['brutto'] ?? 0);
            $whItem->netto               += (float) ($row['netto']  ?? 0);
            $whItem->total_sum           += (float) $row['total_sum'];
            $whItem->additional_expenses += (float) ($row['additional_expenses'] ?? 0);
            $whItem->price                = (float) $row['price'];
            $whItem->cost_price           = (float) ($row['cost_price'] ?? 0);
            $whItem->save();
        }

            /* 3-C. дополнительные расходы */
    // отфильтровываем только те строки, где указана сумма
    $expensesToSave = collect($data['expenses'] ?? [])->filter(function($e) {
        return isset($e['amount']) && $e['amount'] !== null;
    });

    foreach ($expensesToSave as $e) {
        // получаем expense_name_id в любом формате
        $expenseNameId = data_get($e, 'expense_name_id')
                       ?? data_get($e, 'name.id');

        Expense::create([
            'organization_id' => $orgId,
            'document_id'     => $doc->id,
            'provider_id'     => data_get($e, 'provider.id'),
            'expense_name_id' => $expenseNameId,
            'amount'          => (float) $e['amount'],
        ]);
    }


        DB::commit();
        return response()->json([
            'success' => true,
            'message' => "Document {$doc->id} saved",
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('storeIncomes', ['msg' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}




public function updateIncomes(Request $request, Document $document): JsonResponse
    {
        $document->load('documentType','items','expenses');
        if ($document->documentType->code !== 'income') {
            return response()->json(['success'=>false,'error'=>'Not an income document'],422);
        }

        /* 1. Валидация --------------------------------------------------- */
        $v = $request->validate([
            'providerId'            => ['nullable','uuid','exists:providers,id'],
            'assigned_warehouse_id' => ['required','uuid','exists:warehouses,id'],
            'docDate'               => ['nullable','date'],

            'products'                                => ['required','array','min:1'],
            'products.*.product.id'                   => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.name'                    => ['required','string','exists:unit_measurements,name'],

            'products.*.qtyTare'            => ['nullable','numeric'],
            'products.*.quantity'           => ['nullable','numeric'],
            'products.*.price'              => ['nullable','numeric'],
            'products.*.brutto'             => ['nullable','numeric'],
            'products.*.netto'              => ['nullable','numeric'],
            'products.*.total_sum'          => ['nullable','numeric'],
            'products.*.additional_expenses'=> ['nullable','numeric'],
            'products.*.cost_price'         => ['nullable','numeric'],

            'expenses'                      => ['nullable','array'],
            'expenses.*.name.id'            => ['nullable','uuid','exists:expense_names,id'],
            'expenses.*.provider.id'        => ['nullable','uuid','exists:providers,id'],
            'expenses.*.amount'             => ['nullable','numeric'],
        ]);

        /* 2. Шорткаты ---------------------------------------------------- */
        $whId  = $v['assigned_warehouse_id'];
        $prov  = $v['providerId'] ?? null;
        $date  = Carbon::parse($v['docDate'] ?? now())->toDateString();
        $rows  = collect($v['products']);
        $costs = collect($v['expenses'] ?? []);

        DB::beginTransaction();
        try {
            /* 2-A. Откат склада */
            foreach ($document->items as $old) {
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $document->to_warehouse_id,
                            'product_subcard_id' => $old->product_subcard_id,
                            'unit_measurement'   => $old->unit_measurement,
                        ])->first();
                if ($stock) {
                    $stock->quantity  -= $old->quantity;
                    $stock->brutto    -= $old->brutto;
                    $stock->netto     -= $old->netto;
                    $stock->total_sum -= $old->total_sum;
                    $stock->save();
                }
            }

            /* 2-B. Чистим старые строки и расходы */
            $document->items()->delete();
            $document->expenses()->delete();

            /* 2-C. Обновляем шапку */
            $document->update([
                'provider_id'     => $prov,
                'to_warehouse_id' => $whId,
                'document_date'   => $date,
                'comments'        => $request->input('comments',''),
            ]);

            /* 2-D. Новые позиции */
            foreach ($rows as $r) {
                $prodId   = data_get($r,'product.id');
                $unitName = data_get($r,'unit.name');

                $qty   = (float)($r['qtyTare'] ?? $r['quantity'] ?? 0);
                $brut  = (float)($r['brutto'] ?? 0);
                $net   = (float)($r['netto']  ?? 0);
                $sum   = (float)($r['total_sum'] ?? 0);

                /* склад */
                $stock = WarehouseItem::firstOrNew([
                    'warehouse_id'       => $whId,
                    'product_subcard_id' => $prodId,
                    'unit_measurement'   => $unitName,
                ]);

                $stock->quantity  += $qty;
                $stock->brutto    += $brut;
                $stock->netto     += $net;
                $stock->total_sum += $sum;
                $stock->price      = $r['price']      ?? $stock->price ?? 0;
                $stock->cost_price = $r['cost_price'] ?? $stock->cost_price ?? 0;
                $stock->save();

                /* document_items */
                DocumentItem::create([
                    'document_id'         => $document->id,
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $unitName,
                    'quantity'            => $qty,
                    'brutto'              => $brut,
                    'netto'               => $net,
                    'price'               => $r['price']      ?? 0,
                    'total_sum'           => $sum,
                    'additional_expenses' => $r['additional_expenses'] ?? 0,
                    'cost_price'          => $r['cost_price'] ?? 0,
                    'net_unit_weight'     => $qty>0 ? round($net/$qty,4) : 0,
                ]);
            }

            /* 2-E. Новые расходы */
            foreach ($costs as $c) {
                Expense::create([
                    'document_id'     => $document->id,
                    'provider_id'     => data_get($c,'provider.id'),
                    'expense_name_id' => data_get($c,'name.id'),
                    'amount'          => (float)($c['amount'] ?? 0),
                ]);
            }

            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Income document updated',
                'doc_id' =>$document->id
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('updateIncomes', ['msg'=>$e->getMessage()]);
            return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
        }
    }

/**
 * DELETE /income-products/{document}
 */
public function destroyIncomes(Document $document): JsonResponse
    {
        $document->load('documentType','items','expenses');
        if ($document->documentType->code !== 'income') {
            return response()->json(['success'=>false,'error'=>'Not an income document'],422);
        }

        DB::beginTransaction();
        try {
            /* 1. откат склада */
            $whId = $document->to_warehouse_id;
            foreach ($document->items as $row) {
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $row->product_subcard_id,
                            'unit_measurement'   => $row->unit_measurement,
                        ])->first();
                if ($stock) {
                    $stock->quantity  -= $row->quantity;
                    $stock->brutto    -= $row->brutto;
                    $stock->netto     -= $row->netto;
                    $stock->total_sum -= $row->total_sum;
                    $stock->save();
                }
            }

            /* 2. удаляем строки */
            $document->items()->delete();
            $document->expenses()->delete();
            $document->delete();

            DB::commit();
            return response()->json(['success'=>true,'message'=>'Income document deleted']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('destroyIncomes', ['msg'=>$e->getMessage()]);
            return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
        }
    }
}
