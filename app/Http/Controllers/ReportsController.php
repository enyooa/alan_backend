<?php

namespace App\Http\Controllers;

use App\Models\AdminCashes;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Expense;
use App\Models\FinancialElement;
use App\Models\FinancialOrder;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;               // ← add this
use Illuminate\Http\JsonResponse;
class ReportsController extends Controller
{
    public function index()
    {
        // Пример: users с role_id=5
        $warehouses = DB::table('users')
            ->join('role_user','users.id','=','role_user.user_id')
            ->where('role_id', 5)
            ->select('users.id','users.name')
            ->get();

        return response()->json($warehouses, 200);
    }
    /**
     * Отчет по кассе (Cash Flow)
     */
    public function cashFlowReport()
    {
        // Example: retrieve data from your cash_flow_reports table
        // or however you store that data
        $data = DB::table('cash_flow_reports')->get();

        return response()->json($data, 200);
    }

    /**
     * Отчет по складу (Warehouse)
     */
    /**
 * /api/reports/storage
 * ?date_from=2025-04-01&date_to=2025-04-30
 * &warehouse=Основной&product=огурец
 */
// public function getStorageReport(Request $request): JsonResponse
//     {
//         /* ── 1. входные параметры ─────────────────────────────────── */
//         $from      = $request->input('date_from');
//         $to        = $request->input('date_to');
//         $warehouse = $request->input('warehouse');   // необязательный фильтр
//         $product   = $request->input('product');     // необязательный фильтр

//         foreach (['from','to','warehouse','product'] as $v) {
//             if ($$v === 'null' || $$v === '') $$v = null;
//         }

//         if ($from) $from = Carbon::parse($from)->toDateString();   // YYYY-MM-DD
//         if ($to)   $to   = Carbon::parse($to)  ->toDateString();

//         $sqlFrom = $from ?: '1970-01-01';
//         $sqlTo   = $to   ?: now()->toDateString();

//         /* ── 2. выборка «склад + товар» с opening / inbound / outbound ─ */
//         $detail = DB::table('warehouses AS wh')
//             ->join     ('warehouse_items   AS wi',  'wi.warehouse_id',      '=', 'wh.id')
//             ->join     ('product_sub_cards AS psc', 'psc.id',               '=', 'wi.product_subcard_id')
//             ->leftJoin ('documents         AS d', function ($j) {
//                 $j->on('d.to_warehouse_id',   '=', 'wh.id')
//                   ->orOn('d.from_warehouse_id','=', 'wh.id');
//             })
//             ->leftJoin ('document_types    AS dt', 'dt.id',                 '=', 'd.document_type_id')
//             ->leftJoin ('document_items    AS di', function ($j) {
//                 $j->on('di.document_id',       '=', 'd.id')
//                   ->on('di.product_subcard_id','=', 'psc.id');
//             })

//             /* фильтры склада / товара */
//             ->when($warehouse, function ($q) use ($warehouse) {
//                 Str::isUuid($warehouse)
//                     ? $q->where('wh.id', $warehouse)
//                     : $q->whereRaw('LOWER(wh.name) LIKE ?', ['%'.mb_strtolower($warehouse).'%']);
//             })
//             ->when($product, function ($q) use ($product) {
//                 Str::isUuid($product)
//                     ? $q->where('psc.id', $product)
//                     : $q->whereRaw('LOWER(psc.name) LIKE ?', ['%'.mb_strtolower($product).'%']);
//             })

//             ->select(
//                 'wh.id   AS warehouse_id',
//                 'wh.name AS warehouse_name',
//                 'psc.id  AS product_id',
//                 'psc.name AS product_name',
//                 'wi.cost_price',

//                 /* opening (остаток до периода) */
//                 DB::raw("
//                     COALESCE(SUM(
//                       CASE
//                         WHEN d.document_date < '{$sqlFrom}'
//                          AND (
//                               (dt.code IN ('income','transfer')  AND d.to_warehouse_id   = wh.id) OR
//                               (dt.code IN ('sale','write_off','transfer') AND d.from_warehouse_id = wh.id)
//                              )
//                         THEN di.quantity *
//                              CASE
//                                WHEN dt.code IN ('income','transfer') AND d.to_warehouse_id = wh.id
//                                THEN  1 ELSE -1 END
//                         ELSE 0 END
//                     ),0) AS opening
//                 "),
//                 /* inbound (приход в периоде) */
//                 DB::raw("
//                     COALESCE(SUM(
//                       CASE
//                         WHEN d.document_date BETWEEN '{$sqlFrom}' AND '{$sqlTo}'
//                          AND dt.code IN ('income','transfer')
//                          AND d.to_warehouse_id = wh.id
//                         THEN di.quantity ELSE 0 END
//                     ),0) AS total_inbound
//                 "),
//                 /* outbound (расход в периоде) */
//                 DB::raw("
//                     COALESCE(SUM(
//                       CASE
//                         WHEN d.document_date BETWEEN '{$sqlFrom}' AND '{$sqlTo}'
//                          AND dt.code IN ('sale','write_off','transfer')
//                          AND d.from_warehouse_id = wh.id
//                         THEN di.quantity ELSE 0 END
//                     ),0) AS total_outbound
//                 ")
//             )
//             ->groupBy(
//                 'wh.id','wh.name',
//                 'psc.id','psc.name',
//                 'wi.cost_price'
//             )
//             ->orderBy('wh.id')
//             ->orderBy('psc.id')
//             ->get();

//         /* ── 3. рассчитываем closing и стоимость, добавляем opening ── */
//         $detail->transform(function ($r) {
//             $opening         = (float) $r->opening;
//             $closing         = $opening + $r->total_inbound - $r->total_outbound;
//             $remainderValue  = round($closing * $r->cost_price, 2);

//             return (object) [
//                 'warehouse_id'     => $r->warehouse_id,
//                 'warehouse_name'   => $r->warehouse_name,
//                 'product_id'       => $r->product_id,
//                 'product_name'     => $r->product_name,

//                 'opening'          => $opening,                // ← начальный остаток
//                 'total_inbound'    => (float) $r->total_inbound,
//                 'total_outbound'   => (float) $r->total_outbound,
//                 'remainder'        => (float) $closing,        // конечный остаток
//                 'cost_price'       => (float) $r->cost_price,
//                 'remainder_value'  => $remainderValue,
//             ];
//         });

//         /* ── 4. группировка по складам ─────────────────────────────── */
//         $rows = $detail
//             ->groupBy('warehouse_id')
//             ->map(function ($items) {

//                 $first = $items->first();

//                 return [
//                     'warehouse_id'     => $first->warehouse_id,
//                     'warehouse_name'   => $first->warehouse_name,

//                     'opening'          => $items->sum('opening'),      // новый агрегат
//                     'total_inbound'    => $items->sum('total_inbound'),
//                     'total_outbound'   => $items->sum('total_outbound'),
//                     'remainder'        => $items->sum('remainder'),
//                     'remainder_value'  => $items->sum('remainder_value'),

//                     'products' => $items->map(function ($r) {
//                         return [
//                             'product_id'       => $r->product_id,
//                             'product_name'     => $r->product_name,

//                             'opening'          => $r->opening,
//                             'total_inbound'    => $r->total_inbound,
//                             'total_outbound'   => $r->total_outbound,
//                             'remainder'        => $r->remainder,
//                             'cost_price'       => $r->cost_price,
//                             'remainder_value'  => $r->remainder_value,
//                         ];
//                     })->values(),
//                 ];
//             })
//             ->values();

//         /* ── 5. ответ ──────────────────────────────────────────────── */
//         return response()->json([
//             'date_from' => $from,
//             'date_to'   => $to,
//             'rows'      => $rows,
//         ], 200);
//     }
public function getStorageReport(Request $request): JsonResponse
{
    /* ─── 0. приводим "null" → null ────────────────────────────── */
    $request->merge(
        collect(['date_from','date_to'])
            ->mapWithKeys(fn($k)=>[$k=>$request->input($k)])
            ->map(fn($v)=>$v==='null'?null:$v)
            ->all()
    );

    /* ─── 1. validate ──────────────────────────────────────────── */
    $data = $request->validate([
        'date_from' => ['nullable','date'],
        'date_to'   => ['nullable','date','after_or_equal:date_from'],
        'warehouse' => ['nullable','uuid'],
        'page'      => ['nullable','integer','min:1'],
        'per_page'  => ['nullable','integer','between:1,100'],
    ]);

    $orgId   = $request->user()->organization_id;
    $page    = (int)($data['page']     ?? 1);
    $perPage = (int)($data['per_page'] ?? 20);

    $from = $data['date_from'] ?? '1970-01-01';
    $to   = $data['date_to']   ?? now()->toDateString();

    $qty = "COALESCE(NULLIF(di.quantity,0), di.brutto)";

    /* ─── 2. детальная выборка ─────────────────────────────────── */
    $rowsSQL = DB::table('documents AS d')
        ->join('document_types AS dt', 'dt.id','=','d.document_type_id')
        ->join('document_items AS di', 'di.document_id','=','d.id')
        ->join('product_sub_cards AS p','p.id','=','di.product_subcard_id')
        ->join('warehouses AS wh',function($j){
            $j->on('wh.id','=','d.to_warehouse_id')
              ->orOn('wh.id','=','d.from_warehouse_id');
        })
        ->select(
            'wh.id AS warehouse_id','wh.name AS warehouse_name',
            'p.id  AS product_id','p.name AS product_name',
            'di.unit_measurement AS unit',

            /* средняя себестоимость (только приходы) */
            DB::raw("
                ROUND(
                    SUM(
                        CASE
                          WHEN dt.code IN ('income','transfer')
                           AND d.to_warehouse_id = wh.id
                           AND d.document_date <= '{$to}'
                          THEN COALESCE(di.cost_price,0) * {$qty}
                          ELSE 0 END
                    ) /
                    NULLIF(
                        SUM(
                          CASE
                            WHEN dt.code IN ('income','transfer')
                             AND d.to_warehouse_id = wh.id
                             AND d.document_date <= '{$to}'
                            THEN {$qty}
                            ELSE 0 END
                        ),0)
                ,4) AS cost_price
            "),
            /* opening / inbound / outbound */
            DB::raw("
                SUM(CASE
                      WHEN d.document_date < '{$from}'
                       AND (
                            (dt.code IN ('income','transfer')   AND d.to_warehouse_id   = wh.id) OR
                            (dt.code IN ('sale','write_off','transfer') AND d.from_warehouse_id = wh.id)
                           )
                      THEN {$qty} *
                           CASE
                             WHEN dt.code IN ('income','transfer') AND d.to_warehouse_id = wh.id
                             THEN 1 ELSE -1 END
                      ELSE 0 END) AS opening_qty
            "),
            DB::raw("
                SUM(CASE
                      WHEN d.document_date BETWEEN '{$from}' AND '{$to}'
                       AND dt.code IN ('income','transfer')
                       AND d.to_warehouse_id = wh.id
                      THEN {$qty} ELSE 0 END) AS inbound_qty
            "),
            DB::raw("
                SUM(CASE
                      WHEN d.document_date BETWEEN '{$from}' AND '{$to}'
                       AND dt.code IN ('sale','write_off','transfer')
                       AND d.from_warehouse_id = wh.id
                      THEN {$qty} ELSE 0 END) AS outbound_qty
            ")
        )
        ->where('d.organization_id',$orgId);             // 🔒 организация

    if (!empty($data['warehouse']) && $data['warehouse']!=='null') {
        $rowsSQL->where(function($q) use($data){
            $q->where('d.to_warehouse_id',$data['warehouse'])
              ->orWhere('d.from_warehouse_id',$data['warehouse']);
        });
    }

    $detail = $rowsSQL
        ->groupBy('wh.id','wh.name','p.id','p.name','di.unit_measurement')
        ->orderBy('wh.name')->orderBy('p.name')
        ->get();

    /* ─── 3. post-processing строки ─────────────────────────────── */
    $detail->transform(function($r){
        $closing = $r->opening_qty + $r->inbound_qty - $r->outbound_qty;
        return (object)[
            'warehouse_id'   => $r->warehouse_id,
            'warehouse_name' => $r->warehouse_name,
            'product_id'     => $r->product_id,
            'product_name'   => $r->product_name,
            'unit'           => $r->unit,
            'opening'        => (float)$r->opening_qty,
            'total_inbound'  => (float)$r->inbound_qty,
            'total_outbound' => (float)$r->outbound_qty,
            'remainder'      => (float)$closing,
            'cost_price'     => (float)$r->cost_price,
            'remainder_value'=> round($closing * $r->cost_price,2),
        ];
    });

    /* ─── 4. группируем по складам ──────────────────────────────── */
    $byWh = $detail->groupBy('warehouse_id')->map(function($items){
        $wh = $items->first();
        return [
            'warehouse_id'    => $wh->warehouse_id,
            'warehouse_name'  => $wh->warehouse_name,
            'opening'         => $items->sum('opening'),
            'total_inbound'   => $items->sum('total_inbound'),
            'total_outbound'  => $items->sum('total_outbound'),
            'remainder'       => $items->sum('remainder'),
            'remainder_value' => $items->sum('remainder_value'),
            'products'        => $items->values(),
        ];
    })->values();

    /* ─── 5. пагинация коллекции складов ────────────────────────── */
    $totalWh   = $byWh->count();
    $slice     = $byWh->slice(($page-1)*$perPage,$perPage)->values();
    $lastPage  = max(1,(int)ceil($totalWh / $perPage));

    return response()->json([
        'date_from' => $from,
        'date_to'   => $to,
        'rows'      => $slice,
        'meta'      => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => $lastPage,
            'total_warehouses' => $totalWh,
        ],
    ]);
}

    /**
     * Отчет по долгам (Debts)
     */
    // app/Http/Controllers/ReportController.php
/**
 * GET /api/admin-report-debts
 * Унифицированный отчёт:
 *   A) «Приходы» + связанные расходы
 *   B) Клиентские долги
 * Возвращает массив `finalRows`, который использует и веб-страница, и мобильное приложение.
 */
// old version 18.05.2025
// public function debtsReport(Request $request): JsonResponse
// {
//     $dateFrom = $request->input('date_from');   // YYYY-MM-DD
//     $dateTo   = $request->input('date_to');     // YYYY-MM-DD

//     $finalRows = [];                            // итоговый массив строк-объектов

//     /*─────────────────────────────────────────────────────────────
//      | A. Документы-приходы + Расходы
//      *────────────────────────────────────────────────────────────*/
//     $incomeDocs = Document::whereHas(
//                         'documentType', fn ($q) => $q->where('code', 'income')
//                     )
//         ->with([
//             'provider',                  // поставщик-контрагент
//             'documentItems',             // товарные строки
//             'expenses.name'              // расходы + название (ExpenseName)
//         ])
//         ->when($dateFrom && $dateTo,          // фильтр по дате
//             fn ($q) => $q->whereBetween('document_date', [$dateFrom, $dateTo])
//         )
//         ->get()
//         ->groupBy('provider_id');             // группируем по поставщику

//     /* заголовок блока */
//     $finalRows[] = [
//         'row_type' => 'group',
//         'label'    => 'Документы (income) + Расходы',
//         'incoming' => null,
//         'outgoing' => null,
//         'balance'  => null,
//     ];

//     /* ——— цикл по поставщикам ——— */
//     foreach ($incomeDocs as $providerId => $docs) {

//         /* агрегаты по поставщику */
//         $provIncoming = $docs->sum(fn ($d) => $d->documentItems->sum('total_sum'));
//         $provExpense  = $docs->sum(fn ($d) => $d->expenses->sum('amount'));

//         $finalRows[] = [
//             'row_type' => 'provider',
//             'name'     => optional($docs->first()->provider)->name ?? 'Без поставщика',
//             'incoming' => $provIncoming,
//             'outgoing' => $provExpense,
//             'balance'  => $provIncoming - $provExpense,
//         ];

//         /* ——— цикл по отдельным документам этого поставщика ——— */
//         foreach ($docs as $doc) {

//             $docIncoming = $doc->documentItems->sum('total_sum');
//             $docExpense  = $doc->expenses->sum('amount');

//             $finalRows[] = [
//                 'row_type' => 'doc',
//                 'name'     => "Документ #{$doc->id} ({$doc->document_date})",
//                 'incoming' => $docIncoming,
//                 'outgoing' => $docExpense,
//                 'balance'  => $docIncoming - $docExpense,
//             ];

//             /* ——— детальные строки-расходы ——— */
//             foreach ($doc->expenses as $exp) {
//                 $finalRows[] = [
//                     'row_type' => 'expense',
//                     'name'     => optional($exp->name)->name ?? '—',
//                     'incoming' => 0,
//                     'outgoing' => (float) $exp->amount,
//                     'balance'  => -1 * (float) $exp->amount,
//                 ];
//             }
//         }
//     }

//     /*─────────────────────────────────────────────────────────────
//      | B. Долги клиентов
//      *────────────────────────────────────────────────────────────*/
//     $finalRows[] = [
//         'row_type' => 'group',
//         'label'    => 'Отчёт по долгам (client)',
//         'incoming' => null,
//         'outgoing' => null,
//         'balance'  => null,
//     ];

//     /* 1. id всех users с ролью client */
//     $clientIds = DB::table('role_user AS ru')
//         ->join('roles AS r', 'r.id', '=', 'ru.role_id')
//         ->where('r.name', 'client')
//         ->pluck('ru.user_id');

//     /* 2. динамические условия по датам */
//     $foFilter  = $dateFrom && $dateTo
//         ? "AND fo.date_of_check BETWEEN '$dateFrom' AND '$dateTo'"
//         : '';
//     $docFilter = $dateFrom && $dateTo
//         ? "AND d.document_date BETWEEN '$dateFrom' AND '$dateTo'"
//         : '';

//     /* 3. агрегированный SQL */
//     $debtsData = DB::table('users AS c')
//         ->selectRaw("
//             c.id,
//             CONCAT(c.first_name,' ',c.last_name) AS name,

//             /* приходы (платежи) */
//             COALESCE((
//                 SELECT SUM(fo.summary_cash)
//                   FROM financial_orders fo
//                  WHERE fo.user_id = c.id $foFilter
//             ),0) AS incoming,

//             /* расходы (продажи) */
//             COALESCE((
//                 SELECT SUM(di.total_sum)
//                   FROM documents d
//                   JOIN document_types dt ON dt.id = d.document_type_id
//                   JOIN document_items di ON di.document_id = d.id
//                  WHERE d.client_id = c.id
//                    AND dt.code = 'sale' $docFilter
//             ),0) AS outgoing
//         ")
//         ->whereIn('c.id', $clientIds)
//         ->orderBy('c.id')
//         ->get()
//         ->map(function ($r) {
//             $r->balance = $r->incoming - $r->outgoing;
//             return $r;
//         });

//     /* 4. переносим в finalRows */
//     foreach ($debtsData as $r) {
//         $finalRows[] = [
//             'row_type' => 'client',
//             'name'     => $r->name,
//             'incoming' => (float) $r->incoming,
//             'outgoing' => (float) $r->outgoing,
//             'balance'  => (float) $r->balance,
//         ];
//     }

//     return response()->json($finalRows);
// }
// newVersion
/**
 * GET /api/debts-report
 *
 * Параметры:
 *   date_from, date_to      YYYY-MM-DD | null
 *   counterparty            uuid[,uuid] | null
 *   page, per_page          (по-умолчанию 1 / 20)
 *
 * Колонки:
 *   start | income | expense | end
 */
public function debtsReport(Request $request): JsonResponse
{
    /* ── helpers ───────────────────────────────────────────────── */
    $nv = fn($v) => $v === 'null' ? null : $v;

    $orgId    = $request->user()->organization_id;
    $dateFrom = $nv($request->input('date_from'));
    $dateTo   = $nv($request->input('date_to'));
    $csv      = $nv($request->input('counterparty'));

    $page     = max(1,   (int)($request->input('page') ?? 1));
    $perPage  = min(100, max(1,(int)($request->input('per_page') ?? 20)));

    $ctrpList = filled($csv) ? collect(explode(',',$csv))->filter() : collect();

    /* ── 1. поставщики: приход товара + выплаты ────────────────── */
    $provInc = DB::table('documents AS d')
        ->join('document_types AS dt','dt.id','=','d.document_type_id')
        ->join('document_items AS di','di.document_id','=','d.id')
        ->where('dt.code','income')
        ->whereNotNull('d.provider_id')
        ->where('d.organization_id',$orgId)
        ->when($dateFrom, fn($q)=>$q->whereDate('d.document_date','>=',$dateFrom))
        ->when($dateTo,   fn($q)=>$q->whereDate('d.document_date','<=',$dateTo))
        ->when($ctrpList->isNotEmpty(), fn($q)=>$q->whereIn('d.provider_id',$ctrpList))
        ->selectRaw('d.provider_id AS id, SUM(di.total_sum) AS income')
        ->groupBy('d.provider_id');

    $provPay = DB::table('financial_orders')
        ->where('organization_id',$orgId)
        ->where('type','expense')
        ->whereNotNull('provider_id')
        ->when($dateFrom, fn($q)=>$q->whereDate('date_of_check','>=',$dateFrom))
        ->when($dateTo,   fn($q)=>$q->whereDate('date_of_check','<=',$dateTo))
        ->when($ctrpList->isNotEmpty(), fn($q)=>$q->whereIn('provider_id',$ctrpList))
        ->selectRaw('provider_id AS id, SUM(summary_cash) AS expense')
        ->groupBy('provider_id');

    /* ── 2. клиенты: продажи + оплаты ──────────────────────────── */
    $cliSales = DB::table('documents AS d')
        ->join('document_types AS dt','dt.id','=','d.document_type_id')
        ->join('document_items AS di','di.document_id','=','d.id')
        ->where('dt.code','sale')
        ->where('d.status','confirmed')
        ->whereNotNull('d.client_id')
        ->where('d.organization_id',$orgId)
        ->when($dateFrom, fn($q)=>$q->whereDate('d.document_date','>=',$dateFrom))
        ->when($dateTo,   fn($q)=>$q->whereDate('d.document_date','<=',$dateTo))
        ->when($ctrpList->isNotEmpty(), fn($q)=>$q->whereIn('d.client_id',$ctrpList))
        ->selectRaw('d.client_id AS id, SUM(di.total_sum) AS expense')
        ->groupBy('d.client_id');

    $cliPay = DB::table('financial_orders')
        ->where('organization_id',$orgId)
        ->where('type','income')
        ->whereNotNull('user_id')
        ->when($dateFrom, fn($q)=>$q->whereDate('date_of_check','>=',$dateFrom))
        ->when($dateTo,   fn($q)=>$q->whereDate('date_of_check','<=',$dateTo))
        ->when($ctrpList->isNotEmpty(), fn($q)=>$q->whereIn('user_id',$ctrpList))
        ->selectRaw('user_id AS id, SUM(summary_cash) AS income')
        ->groupBy('user_id');

    /* ── 3. сводим движение в обычный массив ───────────────────── */
    $movement = [];                                         // ← array
    foreach ([$provInc,$provPay,$cliSales,$cliPay] as $src) {
        foreach ($src->get() as $r) {
            if (!isset($movement[$r->id])) {
                $movement[$r->id] = ['income'=>0,'expense'=>0];
            }
            if (isset($r->income))  $movement[$r->id]['income']  += $r->income;
            if (isset($r->expense)) $movement[$r->id]['expense'] += $r->expense;
        }
    }

    /* ── 4. opening balance (до date_from) ─────────────────────── */
    $opening = [];
    if ($dateFrom) {
        $openSrc = [
            // +долг поставщику
            DB::table('documents AS d')
              ->join('document_types AS dt','dt.id','=','d.document_type_id')
              ->join('document_items AS di','di.document_id','=','d.id')
              ->where('dt.code','income')
              ->where('d.organization_id',$orgId)
              ->whereDate('d.document_date','<',$dateFrom)
              ->selectRaw('d.provider_id AS id, SUM(di.total_sum) AS plus'),
            // -долг (выплата поставщику)
            DB::table('financial_orders')
              ->where('organization_id',$orgId)
              ->where('type','expense')
              ->whereDate('date_of_check','<',$dateFrom)
              ->selectRaw('provider_id AS id, SUM(summary_cash) AS minus'),
            // -долг клиента (продажа)
            DB::table('documents AS d')
              ->join('document_types AS dt','dt.id','=','d.document_type_id')
              ->join('document_items AS di','di.document_id','=','d.id')
              ->where('dt.code','sale')
              ->where('d.status','confirmed')
              ->where('d.organization_id',$orgId)
              ->whereDate('d.document_date','<',$dateFrom)
              ->selectRaw('d.client_id AS id, SUM(di.total_sum) AS minus'),
            // +долг клиента (оплата)
            DB::table('financial_orders')
              ->where('organization_id',$orgId)
              ->where('type','income')
              ->whereDate('date_of_check','<',$dateFrom)
              ->selectRaw('user_id AS id, SUM(summary_cash) AS plus'),
        ];

        foreach ($openSrc as $q) {
            foreach ($q->groupBy('id')->get() as $r) {
                $opening[$r->id] ??= 0;
                if (isset($r->plus))  $opening[$r->id] += $r->plus;
                if (isset($r->minus)) $opening[$r->id] -= $r->minus;
            }
        }
    }

    /* ── 5. сборка отчёта ──────────────────────────────────────── */
    $rows = collect($movement)->map(function($m,$id) use ($opening){
        $start   = $opening[$id] ?? 0;
        $income  = $m['income'];
        $expense = $m['expense'];
        $end     = round($start + $income - $expense,2);

        return [
            'counterparty_id' => $id,
            'name'   => self::nameOfCounterparty($id),
            'start'  => round($start ,2),
            'income' => round($income,2),
            'expense'=> round($expense,2),
            'end'    => $end,
        ];
    })
    ->filter(fn($r)=>$r['end']!=0)
    ->sortBy('name')
    ->values();

    /* ── 6. пагинация ──────────────────────────────────────────── */
    $total = $rows->count();
    $slice = $rows->slice(($page-1)*$perPage,$perPage)->values();

    return response()->json([
        'data' => $slice,
        'meta' => [
            'current_page'=> $page,
            'per_page'    => $perPage,
            'last_page'   => max(1,(int)ceil($total/$perPage)),
            'total_rows'  => $total,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
        ],
    ]);
}

/** имя контрагента */
private static function nameOfCounterparty(string $uuid): string
{
    static $cache = [];
    return $cache[$uuid] ??= (
        Provider::find($uuid)->name ??
        optional(User::find($uuid))->full_name ??
        '—'
    );
}



//     public function debtsReport(Request $request): JsonResponse
// {
//     /* ───── 1. normalise inputs ───── */
//     $from = $request->query('date_from');
//     $to   = $request->query('date_to');
//     $cli  = $request->query('client');
//     $flow = $request->query('flow', 'all');          // all | income | expense

//     foreach (['from','to','cli','flow'] as $v) {
//         if ($$v === '' || $$v === 'null') $$v = null;
//     }
//     if (!in_array($flow, ['income','expense','all'], true)) {
//         $flow = 'all';
//     }

//     $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::parse('1970-01-01');
//     $to   = $to   ? Carbon::parse($to  )->endOfDay()   : now()->endOfDay();

//     /* ───── 2. load client list (role=client) ───── */
//     $clients = User::query()
//         ->when($cli, function($q) use($cli) {
//             if (Str::isUuid($cli)) {
//                 $q->where('id', $cli);
//             } else {
//                 $q->whereRaw(
//                     "LOWER(CONCAT_WS(' ', first_name, last_name, surname)) LIKE ?",
//                     ['%'.mb_strtolower($cli).'%']
//                 );
//             }
//         })
//         ->whereHas('roles', fn($r)=>$r->where('name','client'))
//         ->select('id','first_name','last_name','surname')
//         ->get();

//     if ($clients->isEmpty()) {
//         return response()->json([
//             'date_from' => $from->toDateString(),
//             'date_to'   => $to->toDateString(),
//             'flow'      => $flow ?? 'all',
//             'rows'      => [],
//         ], 200);
//     }

//     $clientIds = $clients->pluck('id');

//     /* ───── 3. opening balance (до $from) ───── */
//     $incomeBefore = FinancialOrder::where('type','income')
//         ->whereIn('user_id',$clientIds)
//         ->whereDate('date_of_check','<',$from)
//         ->groupBy('user_id')
//         ->selectRaw('user_id, SUM(summary_cash) as sum')
//         ->pluck('sum','user_id');                         // [user_id=>sum]

//     $salesBefore  = DB::table('documents AS d')
//         ->join('document_types AS dt','dt.id','=','d.document_type_id')
//         ->join('document_items AS di','di.document_id','=','d.id')
//         ->where('dt.code','sale')
//         ->whereIn('d.client_id',$clientIds)
//         ->whereDate('d.document_date','<',$from)
//         ->groupBy('d.client_id')
//         ->selectRaw('d.client_id, SUM(di.total_sum) AS sum')
//         ->pluck('sum','client_id');                       // [client_id=>sum]

//     /* ───── 4. flows inside period ───── */
//     $incomeNow = FinancialOrder::where('type','income')
//         ->whereIn('user_id',$clientIds)
//         ->whereBetween('date_of_check',[$from,$to])
//         ->groupBy('user_id')
//         ->selectRaw('user_id, SUM(summary_cash) as sum')
//         ->pluck('sum','user_id');                         // [user_id=>sum]

//     $salesNow  = DB::table('documents AS d')
//         ->join('document_types AS dt','dt.id','=','d.document_type_id')
//         ->join('document_items AS di','di.document_id','=','d.id')
//         ->where('dt.code','sale')
//         ->whereIn('d.client_id',$clientIds)
//         ->whereBetween('d.document_date',[$from,$to])
//         ->groupBy('d.client_id')
//         ->selectRaw('d.client_id, SUM(di.total_sum) AS sum')
//         ->pluck('sum','client_id');                       // [client_id=>sum]

//     /* ───── 5. build rows ───── */
//     $rows = $clients->map(function($c) use ($incomeBefore,$salesBefore,$incomeNow,$salesNow,$flow) {

//         $opening  = ($incomeBefore[$c->id] ?? 0) - ($salesBefore[$c->id] ?? 0);

//         $incoming = $incomeNow[$c->id] ?? 0;   // платежи клиента
//         $outgoing = $salesNow [$c->id] ?? 0;   // отгружено товара

//         /* apply flow filter */
//         $inc = ($flow === 'expense') ? 0 : $incoming;
//         $out = ($flow === 'income' ) ? 0 : $outgoing;

//         return [
//             'client' => [
//                 'id'   => $c->id,
//                 'name' => trim("{$c->first_name} {$c->last_name} {$c->surname}") ?: '—',
//             ],
//             'opening'  => round($opening ,2),
//             'incoming' => round($inc     ,2),
//             'outgoing' => round($out     ,2),
//             'closing'  => round($opening + $inc - $out ,2),
//         ];
//     })->values();

//     return response()->json([
//         'date_from' => $from->toDateString(),
//         'date_to'   => $to  ->toDateString(),
//         'flow'      => $flow ?? 'all',
//         'rows'      => $rows,
//     ], 200);
// }
    /**
     * Отчет по продажам (Sales)
     * старая
     */

//      public function getSalesReport(Request $request): JsonResponse
// {
//     /* ───── 1. «Нормализуем» вход ───── */
//     $from  = $request->input('date_from');
//     $to    = $request->input('date_to');
//     $buyer = $request->input('client');
//     $prod  = $request->input('product');

//     // превращаем 'null' или '' в null
//     foreach (['from','to','buyer','prod'] as $v) {
//         if ($$v === 'null' || $$v === '') {
//             $$v = null;
//         }
//     }

//     /* ───── 2. Загружаем только документы-sale ───── */
//     $docs = Document::whereHas('documentType', function ($q) {
//                 $q->where('code', 'sale');
//             })
//             ->with([
//                 'client:id,first_name,last_name,surname',
//                 'documentItems.product:id,name'
//             ])

//             // даты
//             ->when($from && $to, function ($q) use ($from, $to) {
//                 $q->whereBetween('document_date', [$from, $to]);
//             })
//             ->when($from && !$to, function ($q) use ($from) {
//                 $q->whereDate('document_date', '>=', $from);
//             })
//             ->when(!$from && $to, function ($q) use ($to) {
//                 $q->whereDate('document_date', '<=', $to);
//             })

//             // клиент
//             ->when($buyer, function ($q) use ($buyer) {
//                 $q->whereHas('client', function ($s) use ($buyer) {
//                     Str::isUuid($buyer)
//                         ? $s->where('id', $buyer)
//                         : $s->whereRaw(
//                             "LOWER(CONCAT_WS(' ', first_name, last_name, surname)) LIKE ?",
//                             ['%'.mb_strtolower($buyer).'%']
//                           );
//                 });
//             })

//             // товар
//             ->when($prod, function ($q) use ($prod) {
//                 $q->whereHas('documentItems.product', function ($s) use ($prod) {
//                     Str::isUuid($prod)
//                         ? $s->where('id', $prod)
//                         : $s->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($prod).'%']);
//                 });
//             })
//             ->get();

//     /* ───── 3. Формируем строки отчёта ───── */
//     $rows = [];

//     foreach ($docs as $doc) {
//         $warehouseId = $doc->from_warehouse_id;        // склад списания

//         foreach ($doc->documentItems as $item) {

//             /* суммы продажи */
//             $qty      = (float) $item->quantity;
//             $saleAmt  = $item->price * $qty;

//             /* себестоимость */
//             $costPrice = 0;
//             if ($warehouseId) {
//                 $wi = WarehouseItem::where('warehouse_id', $warehouseId)
//                     ->where('product_subcard_id', $item->product_subcard_id)
//                     ->first();
//                 $costPrice = $wi ? (float) $wi->cost_price : 0;
//             }
//             $costAmt = $costPrice * $qty;

//             /* клиент */
//             $cl   = $doc->client;                                // может быть null
//             $name = trim(
//                         ($cl->first_name ?? '').' '.
//                         ($cl->last_name  ?? '').' '.
//                         ($cl->surname    ?? '')
//                     ) ?: '—';

//             /* строка */
//             $rows[] = [
//                 'client' => [
//                     'id'   => $cl->id   ?? null,
//                     'name' => $name,
//                 ],
//                 'product' => [
//                     'id'   => $item->product->id   ?? null,
//                     'name' => $item->product->name ?? '—',
//                 ],
//                 'document_date' => Carbon::parse($doc->document_date)->toDateString(),
//                 'quantity'      => $qty,
//                 'sale_amount'   => round($saleAmt , 2),
//                 'cost_amount'   => round($costAmt , 2),
//                 'profit'        => round($saleAmt - $costAmt, 2),
//             ];
//         }
//     }

//     return response()->json($rows, 200);
// }

    // новая версия отчета по продажам
 public function getSalesReport(Request $request): JsonResponse
{
    /* 0. "null" → null */
    $request->merge(
        collect(['date_from','date_to','product','client'])
            ->mapWithKeys(fn($k)=>[$k=>$request->input($k)])
            ->map(fn($v)=>$v==='null'?null:$v)
            ->all()
    );

    /* 1. validate */
    $data = $request->validate([
        'date_from' => ['nullable','date'],
        'date_to'   => ['nullable','date','after_or_equal:date_from'],
        'product'   => ['nullable','string'],
        'client'    => ['nullable','string'],
        'page'      => ['nullable','integer','min:1'],
        'per_page'  => ['nullable','integer','between:1,100'],
    ]);

    $orgId    = $request->user()->organization_id;     // 🔑 организация
    $page     = (int)($data['page']     ?? 1);
    $perPage  = (int)($data['per_page'] ?? 20);

    $products = filled($data['product'] ?? null)
        ? collect(explode(',',$data['product']))->filter()
        : collect();

    $clients  = filled($data['client'] ?? null)
        ? collect(explode(',',$data['client']))->filter()
        : collect();

    /* 2. агрегированный запрос */
    $rows = DB::table('document_items    AS di')
        ->join('documents          AS d',  'd.id','=','di.document_id')
        ->join('document_types     AS dt', 'dt.id','=','d.document_type_id')
        ->join('users              AS u',  'u.id','=','d.client_id')
        ->join('product_sub_cards  AS p',  'p.id','=','di.product_subcard_id')
        ->selectRaw("
            d.client_id,
            CONCAT_WS(' ',u.last_name,u.first_name,u.surname)          AS client_name,
            di.product_subcard_id,
            di.unit_measurement                                       AS unit,
            p.name                                                    AS product_name,
            SUM(di.quantity)                                          AS qty,
            SUM(di.total_sum)                                         AS sale_sum,
            SUM(di.cost_price * di.quantity)                          AS total_cost,
            SUM(di.cost_price * di.quantity) /
              NULLIF(SUM(di.quantity),0)                              AS raw_avg_cost
        ")
        ->where('dt.code','sale')
        ->where('d.status','confirmed')
        ->where('d.organization_id',$orgId);          // 🔒 организация

    /* даты */
    if ($data['date_from']) $rows->whereDate('d.document_date','>=',$data['date_from']);
    if ($data['date_to'])   $rows->whereDate('d.document_date','<=',$data['date_to']);

    /* фильтр списков */
    if ($products->isNotEmpty()) $rows->whereIn('di.product_subcard_id',$products);
    if ($clients ->isNotEmpty()) $rows->whereIn('d.client_id',$clients);

    $rows = $rows->groupByRaw('d.client_id, di.product_subcard_id, di.unit_measurement')
                 ->orderByRaw('client_name, product_name, unit')
                 ->get();

    /* 3. группировка по клиентам */
    $byClient = $rows->groupBy('client_id')->map(function ($items) {
        $sale = $items->sum('sale_sum');
        $cost = $items->sum('total_cost');

        return [
            'client_id'   => $items->first()->client_id,
            'client_name' => $items->first()->client_name,
            'quantity'    => $items->sum('qty'),
            'sale_sum'    => round($sale,2),
            'cost_sum'    => round($cost,2),
            'profit'      => round($sale-$cost,2),
            'products'    => $items->map(fn($r)=>[
                'product_id'   => $r->product_subcard_id,
                'product_name' => $r->product_name,
                'unit'         => $r->unit,
                'quantity'     => $r->qty,
                'sale_sum'     => round($r->sale_sum,2),
                'avg_cost'     => round($r->raw_avg_cost,4),
                'cost_sum'     => round($r->total_cost,2),
                'profit'       => round($r->sale_sum - $r->total_cost,2),
            ])->values(),
        ];
    })->values();

    /* 4. ручная пагинация коллекции клиентов */
    $totalClients = $byClient->count();
    $slice = $byClient->slice(($page-1)*$perPage, $perPage)->values();

    /* 5. итог по выборке (до пагинации) */
    $totalSale = $rows->sum('sale_sum');
    $totalCost = $rows->sum('total_cost');

    return response()->json([
        'data'  => $slice,
        'total' => [
            'quantity' => $rows->sum('qty'),
            'sale_sum' => round($totalSale,2),
            'cost_sum' => round($totalCost,2),
            'profit'   => round($totalSale - $totalCost,2),
        ],
        'meta'  => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => max(1, (int)ceil($totalClients / $perPage)),
            'total_clients'=> $totalClients,
        ],
    ]);
}
   public function cash_report(Request $request): JsonResponse
{
    /* ─── 0. helper ------------------------------------------------ */
    $toNull = fn($v)=>$v==='null'?null:$v;

    /* ─── 1. нормализация ----------------------------------------- */
    $from = self::normalizeDate($toNull($request->input('date_from')));
    $to   = self::normalizeDate($toNull($request->input('date_to')));
    $cbx  = self::normalizeText($toNull($request->input('cashbox')));
    $elt  = self::normalizeText($toNull($request->input('element')));

    $page    = max(1,   (int)($request->input('page'     ) ?? 1));
    $perPage = min(100, max(1,(int)($request->input('per_page') ?? 20)));

    $orgId = $request->user()->organization_id;               // 🔒 организация

    /* ─── 2. выборка ордеров -------------------------------------- */
    $orders = FinancialOrder::with(['adminCash:id,name','financialElement:id,name'])
        ->where('organization_id',$orgId)
        ->when($from, fn($q)=>$q->whereDate('date_of_check','>=',$from))
        ->when($to  , fn($q)=>$q->whereDate('date_of_check','<=',$to  ))
        ->when($cbx , fn($q)=>$q->whereHas('adminCash',        fn($s)=>self::filterName($s,$cbx)))
        ->when($elt , fn($q)=>$q->whereHas('financialElement', fn($s)=>self::filterName($s,$elt)))
        ->get();

    /* ─── 3. начальный остаток ------------------------------------ */
    $prevBalances = [];
    if ($from) {
        $before = FinancialOrder::with('adminCash:id,name')
            ->where('organization_id',$orgId)
            ->when($cbx , fn($q)=>$q->whereHas('adminCash',        fn($s)=>self::filterName($s,$cbx)))
            ->when($elt , fn($q)=>$q->whereHas('financialElement', fn($s)=>self::filterName($s,$elt)))
            ->whereDate('date_of_check','<',$from)
            ->get();

        $prevBalances = $before->groupBy('admin_cash_id')->map(function($g){
            return round(
                $g->where('type','income' )->sum('summary_cash')
              - $g->where('type','expense')->sum('summary_cash')
            ,3);
        })->toArray();
    }

    /* ─── 4. агрегируем отчёт (по кассам) ------------------------- */
    $report = $orders->groupBy('admin_cash_id')->map(function($grp,$cid) use ($prevBalances){
        $cash    = $grp->first()->adminCash;
        $income  = $grp->where('type','income' )->sum('summary_cash');
        $expense = $grp->where('type','expense')->sum('summary_cash');
        $start   = $prevBalances[$cid] ?? 0;
        $end     = round($start + $income - $expense,3);

        $elements = $grp->groupBy('financial_element_id')->map(function($eg){
            $el = $eg->first()->financialElement;
            return [
                'id'      => $el->id   ?? null,
                'element' => $el->name ?? '—',
                'income'  => round($eg->where('type','income' )->sum('summary_cash'),3),
                'expense' => round($eg->where('type','expense')->sum('summary_cash'),3),
            ];
        })->values();

        return [
            'cashbox' => [
                'id'   => $cash->id   ?? null,
                'name' => $cash->name ?? '—',
            ],
            'start'    => $start,
            'income'   => round($income ,3),
            'expense'  => round($expense,3),
            'end'      => $end,
            'elements' => $elements,
        ];
    })->values();

    /* ─── 5. пагинация коллекции касс ----------------------------- */
    $totalCbx = $report->count();
    $slice    = $report->slice(($page-1)*$perPage,$perPage)->values();
    $lastPage = max(1,(int)ceil($totalCbx / $perPage));

    return response()->json([
        'data' => $slice,
        'meta' => [
            'current_page'   => $page,
            'per_page'       => $perPage,
            'last_page'      => $lastPage,
            'total_cashboxes'=> $totalCbx,
            'date_from'      => $from,
            'date_to'        => $to,
        ],
    ]);
}

/* ───────────────── помощники ───────────────── */

private static function normalizeDate($val)
{
    return ($val === 'null' || $val === '' || $val === null)
         ? null
         : Carbon::parse($val)->toDateString();   // YYYY-MM-DD
}

private static function normalizeText($val)
{
    if ($val === 'null' || $val === null) return null;

    // убираем кавычки и пробелы
    $clean = trim($val, " \t\n\r\0\x0B\"'“”«»");
    return $clean === '' ? null : $clean;
}

private static function filterName($q, string $needle)
{
    return Str::isUuid($needle)
        ? $q->where('id',$needle)
        : $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($needle).'%']);
}

    public function exportPdf(Request $request)
    {
        // Логика та же, что и в getSalesData
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $query = Document::where('document_type_id', 3);

        if ($startDate && $endDate) {
            $query->whereBetween('document_date', [$startDate, $endDate]);
        }

        $documents = $query->with(['documentItems.product'])->get();

        $data = [];

        foreach ($documents as $doc) {
            foreach ($doc->documentItems as $item) {
                $quantity   = $item->quantity;
                $saleAmount = $item->price * $quantity;
                $costPrice  = $item->cost_price * $quantity;
                $profit     = $saleAmount - $costPrice;
                $reportDate = $doc->document_date;
                $productName = $item->product ? $item->product->name : '—';

                $data[] = [
                    'product_name' => $productName,
                    'quantity'     => $quantity,
                    'sale_amount'  => $saleAmount,
                    'cost_price'   => $costPrice,
                    'profit'       => $profit,
                    'report_date'  => $reportDate,
                ];
            }
        }

        // Генерация PDF (используем barryvdh/laravel-dompdf)
        // $pdf = PDF::loadView('reports.sales_pdf', ['data' => $data]);

        // Выгружаем как скачивание (можно inline, как нужно)
        // return $pdf->download('sales_report.pdf');
        return "";
    }

    public function exportExcel(Request $request)
    {
        // Вариант с maatwebsite/excel
        // В экспорт можно передать те же параметры (даты) и собрать данные там.

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        return "";
        // return Excel::download(new SalesReportExport($startDate, $endDate), 'sales_report.xlsx');
    }
}
