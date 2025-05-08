<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Unit_measurement;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;  // ← import Str

class WriteoffIncomesController extends Controller
{

    /*──────────────────────────────────────
    | 1. Список документов «Списание»
    *──────────────────────────────────────*/
    public function indexWriteOff(): JsonResponse
    {
        $docs = Document::with([
                    'fromWarehouse:id,name',
                    'items',                 // сами строки
                    'items.product',         // товар-object  (сделайте relation product() в модели)
                    'items.unitByName',      // единица-object (relation unitByName() в модели)
                ])
                ->whereHas('documentType', fn($q) => $q->where('code','write_off'))
                ->orderByDesc('document_date')
                ->get();

        return response()->json($docs);
    }


    /*──────────────────────────────────────
    | 2. Создание «Списания»
    *──────────────────────────────────────*/
    public function postWriteOff(Request $request): JsonResponse
{
    /* 1.  VALIDATION ───────────────────────────────────────── */
    $v = $request->validate([
        'docDate'               => ['required', 'date'],
        'assigned_warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],

        'products'                                      => ['required','array','min:1'],
        'products.*.product.product_subcard_id'         => ['required','uuid','exists:product_sub_cards,id'],
        'products.*.unit.id'                            => ['required','uuid','exists:unit_measurements,id'],

        'products.*.qty'        => ['required','numeric','gt:0'],
        'products.*.brutto'     => ['nullable','numeric','gte:0'],
        'products.*.netto'      => ['nullable','numeric','gte:0'],
        'products.*.price'      => ['nullable','numeric','gte:0'],
        'products.*.total_sum'  => ['nullable','numeric','gte:0'],
    ]);

    /* 2.  SHORTCUTS ─────────────────────────────────────────── */
    $rows    = $v['products'];
    $whId    = $v['assigned_warehouse_id'];   // uuid
    $docDate = Carbon::parse($v['docDate'])->toDateString();
    $orgId   = $request->user()->organization_id;   // 👈 ваша организация

    /* 3.  TRANSACTION  ─────────────────────────────────────── */
    DB::beginTransaction();
    try {
        /* 3-A. header */
        $typeWriteOff = DocumentType::where('code', 'write_off')->firstOrFail();

        $doc = Document::create([
            'organization_id'  => $orgId,          // 👈 Сохраняем организацию
            'document_type_id' => $typeWriteOff->id,
            'status'           => '-',
            'from_warehouse_id'=> $whId,
            'document_date'    => $docDate,
            'comments'         => $request->string('comments')->value() ?? '',
        ]);

        /* 3-B. rows + stock update */
        $lines = [];
        foreach ($rows as $row) {
            $prodId   = data_get($row, 'product.product_subcard_id');
            $unitId   = data_get($row, 'unit.id');
            $unitName = Unit_measurement::findOrFail($unitId)->name;
            $qty      = (float) $row['qty'];

            /* складская запись */
            $stock = WarehouseItem::where([
                        'warehouse_id'       => $whId,
                        'product_subcard_id' => $prodId,
                        'unit_measurement'   => $unitName,
                    ])->first();

            throw_if(!$stock,  \Exception::class, "Товар {$prodId} отсутствует на складе");
            throw_if($stock->quantity < $qty,
                     \Exception::class,
                     "Недостаточно остатка ({$prodId} / {$unitName})");

            /* пропорционально списываем «долю» */
            $ratio = $qty / $stock->quantity;

            $woBrut = round($stock->brutto  * $ratio, 2);
            $woNet  = round($stock->netto   * $ratio, 2);
            $woSum  = round($stock->total_sum           * $ratio, 2);
            $woAdd  = round($stock->additional_expenses * $ratio, 2);

            /* write-off со склада */
            $stock->decrementEach([
                'quantity'            => $qty,
                'brutto'              => $woBrut,
                'netto'               => $woNet,
                'total_sum'           => $woSum,
                'additional_expenses' => $woAdd,
            ]);

            /* строка документа */
            $lines[] = [
                'id'                  => Str::uuid(),          // если у items UUID-PK
                'document_id'         => $doc->id,
                'product_subcard_id'  => $prodId,
                'unit_measurement'    => $unitName,
                'quantity'            => $qty,
                'brutto'              => $woBrut,
                'netto'               => $woNet,
                'price'               => $row['price']      ?? $stock->price ?? 0,
                'total_sum'           => $woSum,
                'cost_price'          => $stock->cost_price ?? 0,
                'additional_expenses' => $woAdd,
                'net_unit_weight'     => $qty > 0 ? round($woNet / $qty, 4) : 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        DocumentItem::insert($lines);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Write-off saved',
            'doc_id'  => $doc->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('postWriteOff', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}



    /*──────────────────────────────────────
    | 3. Обновление «Списания»
    *──────────────────────────────────────*/
    public function updateWriteOff(Request $request, Document $document): JsonResponse
    {
        if (!$document->documentType || $document->documentType->code !== 'write_off') {
            return response()->json(['error'=>'Not a write-off document'], 422);
        }

        $v = $request->validate([
            'assigned_warehouse_id'                         => ['required','uuid','exists:warehouses,id'],
            'docDate'                                       => ['required','date'],
            'products'                                      => ['required','array','min:1'],
            'products.*.product.product_subcard_id'         => ['required','uuid','exists:product_sub_cards,id'],
            'products.*.unit.id'                            => ['required','uuid','exists:unit_measurements,id'],
            'products.*.qty'        => ['required','numeric'],
            'products.*.brutto'     => ['nullable','numeric'],
            'products.*.netto'      => ['nullable','numeric'],
            'products.*.price'      => ['nullable','numeric'],
            'products.*.total_sum'  => ['nullable','numeric'],
        ]);

        $whId    = $v['assigned_warehouse_id'];
        $rowsNew = $v['products'];
        $date    = Carbon::parse($v['docDate'])->toDateString();

        DB::beginTransaction();
        try {
            /* 1. вернём старые остатки */
            foreach ($document->items as $old) {
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $document->from_warehouse_id,
                            'product_subcard_id' => $old->product_subcard_id,
                            'unit_measurement'   => $old->unit_measurement,
                        ])->first();
                if ($stock) {
                    $stock->quantity            += $old->quantity;
                    $stock->brutto              += $old->brutto;
                    $stock->netto               += $old->netto;
                    $stock->total_sum           += $old->total_sum;
                    $stock->additional_expenses += $old->additional_expenses;
                    $stock->save();
                }
            }
            $document->items()->delete();

            /* 2. применяем новые строки (та же логика, что в postWriteOff) */
            $insert = [];
            foreach ($rowsNew as $row) {

                $prodId   = data_get($row,'product.product_subcard_id');
                $unitId   = data_get($row,'unit.id');
                $unitName = Unit_measurement::findOrFail($unitId)->name;
                $qty      = (float)$row['qty'];

                $stock = WarehouseItem::where([
                            'warehouse_id'       => $whId,
                            'product_subcard_id' => $prodId,
                            'unit_measurement'   => $unitName,
                        ])->first();

                if (!$stock || $stock->quantity < $qty) {
                    throw new \Exception("Недостаточно остатка (#$prodId / $unitName)");
                }

                $ratio   = $qty / $stock->quantity;
                $woBrut  = round($stock->brutto  * $ratio, 2);
                $woNet   = round($stock->netto   * $ratio, 2);
                $woSum   = round($stock->total_sum           * $ratio, 2);
                $woAdd   = round($stock->additional_expenses * $ratio, 2);

                $stock->quantity            -= $qty;
                $stock->brutto              -= $woBrut;
                $stock->netto               -= $woNet;
                $stock->total_sum           -= $woSum;
                $stock->additional_expenses -= $woAdd;
                $stock->save();

                $insert[] = [
                    'document_id'         => $document->id,
                    'product_subcard_id'  => $prodId,
                    'unit_measurement'    => $unitName,
                    'quantity'            => $qty,
                    'brutto'              => $woBrut,
                    'netto'               => $woNet,
                    'price'               => $row['price']      ?? $stock->price ?? 0,
                    'total_sum'           => $woSum,
                    'cost_price'          => $stock->cost_price ?? 0,
                    'additional_expenses' => $woAdd,
                    'net_unit_weight'     => $qty>0 ? round($woNet/$qty,4) : 0,
                ];
            }
            DocumentItem::insert($insert);

            /* 3. обновляем «шапку» */
            $document->update([
                'from_warehouse_id' => $whId,
                'document_date'     => $date,
                'comments'          => $request->input('comments',''),
            ]);

            DB::commit();
            return response()->json(['success'=>true,'message'=>'Write-off updated'],200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('updateWriteOff error: '.$e->getMessage());
            return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
        }
    }


    /*──────────────────────────────────────
    | 4. Удаление «Списания»
    *──────────────────────────────────────*/
    public function deleteWriteOff(Document $document): JsonResponse
    {
        if (!$document->documentType || $document->documentType->code !== 'write_off') {
            return response()->json(['error'=>'Not a write-off document'],422);
        }

        DB::beginTransaction();
        try {
            /* вернуть остатки */
            foreach ($document->items as $row) {
                $stock = WarehouseItem::where([
                            'warehouse_id'       => $document->from_warehouse_id,
                            'product_subcard_id' => $row->product_subcard_id,
                            'unit_measurement'   => $row->unit_measurement,
                        ])->first();
                if ($stock) {
                    $stock->quantity            += $row->quantity;
                    $stock->brutto              += $row->brutto;
                    $stock->netto               += $row->netto;
                    $stock->total_sum           += $row->total_sum;
                    $stock->additional_expenses += $row->additional_expenses;
                    $stock->save();
                }
            }

            $document->items()->delete();
            $document->delete();

            DB::commit();
            return response()->json(['success'=>true,'message'=>'Write-off deleted'],200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('destroyWriteOff error: '.$e->getMessage());
            return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
        }
    }
    }
