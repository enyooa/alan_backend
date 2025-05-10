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
public function getStorageReport(Request $request): JsonResponse
    {
        /* ── 1. входные параметры ─────────────────────────────────── */
        $from      = $request->input('date_from');
        $to        = $request->input('date_to');
        $warehouse = $request->input('warehouse');   // необязательный фильтр
        $product   = $request->input('product');     // необязательный фильтр

        foreach (['from','to','warehouse','product'] as $v) {
            if ($$v === 'null' || $$v === '') $$v = null;
        }

        if ($from) $from = Carbon::parse($from)->toDateString();   // YYYY-MM-DD
        if ($to)   $to   = Carbon::parse($to)  ->toDateString();

        $sqlFrom = $from ?: '1970-01-01';
        $sqlTo   = $to   ?: now()->toDateString();

        /* ── 2. выборка «склад + товар» с opening / inbound / outbound ─ */
        $detail = DB::table('warehouses AS wh')
            ->join     ('warehouse_items   AS wi',  'wi.warehouse_id',      '=', 'wh.id')
            ->join     ('product_sub_cards AS psc', 'psc.id',               '=', 'wi.product_subcard_id')
            ->leftJoin ('documents         AS d', function ($j) {
                $j->on('d.to_warehouse_id',   '=', 'wh.id')
                  ->orOn('d.from_warehouse_id','=', 'wh.id');
            })
            ->leftJoin ('document_types    AS dt', 'dt.id',                 '=', 'd.document_type_id')
            ->leftJoin ('document_items    AS di', function ($j) {
                $j->on('di.document_id',       '=', 'd.id')
                  ->on('di.product_subcard_id','=', 'psc.id');
            })

            /* фильтры склада / товара */
            ->when($warehouse, function ($q) use ($warehouse) {
                Str::isUuid($warehouse)
                    ? $q->where('wh.id', $warehouse)
                    : $q->whereRaw('LOWER(wh.name) LIKE ?', ['%'.mb_strtolower($warehouse).'%']);
            })
            ->when($product, function ($q) use ($product) {
                Str::isUuid($product)
                    ? $q->where('psc.id', $product)
                    : $q->whereRaw('LOWER(psc.name) LIKE ?', ['%'.mb_strtolower($product).'%']);
            })

            ->select(
                'wh.id   AS warehouse_id',
                'wh.name AS warehouse_name',
                'psc.id  AS product_id',
                'psc.name AS product_name',
                'wi.cost_price',

                /* opening (остаток до периода) */
                DB::raw("
                    COALESCE(SUM(
                      CASE
                        WHEN d.document_date < '{$sqlFrom}'
                         AND (
                              (dt.code IN ('income','transfer')  AND d.to_warehouse_id   = wh.id) OR
                              (dt.code IN ('sale','write_off','transfer') AND d.from_warehouse_id = wh.id)
                             )
                        THEN di.quantity *
                             CASE
                               WHEN dt.code IN ('income','transfer') AND d.to_warehouse_id = wh.id
                               THEN  1 ELSE -1 END
                        ELSE 0 END
                    ),0) AS opening
                "),
                /* inbound (приход в периоде) */
                DB::raw("
                    COALESCE(SUM(
                      CASE
                        WHEN d.document_date BETWEEN '{$sqlFrom}' AND '{$sqlTo}'
                         AND dt.code IN ('income','transfer')
                         AND d.to_warehouse_id = wh.id
                        THEN di.quantity ELSE 0 END
                    ),0) AS total_inbound
                "),
                /* outbound (расход в периоде) */
                DB::raw("
                    COALESCE(SUM(
                      CASE
                        WHEN d.document_date BETWEEN '{$sqlFrom}' AND '{$sqlTo}'
                         AND dt.code IN ('sale','write_off','transfer')
                         AND d.from_warehouse_id = wh.id
                        THEN di.quantity ELSE 0 END
                    ),0) AS total_outbound
                ")
            )
            ->groupBy(
                'wh.id','wh.name',
                'psc.id','psc.name',
                'wi.cost_price'
            )
            ->orderBy('wh.id')
            ->orderBy('psc.id')
            ->get();

        /* ── 3. рассчитываем closing и стоимость, добавляем opening ── */
        $detail->transform(function ($r) {
            $opening         = (float) $r->opening;
            $closing         = $opening + $r->total_inbound - $r->total_outbound;
            $remainderValue  = round($closing * $r->cost_price, 2);

            return (object) [
                'warehouse_id'     => $r->warehouse_id,
                'warehouse_name'   => $r->warehouse_name,
                'product_id'       => $r->product_id,
                'product_name'     => $r->product_name,

                'opening'          => $opening,                // ← начальный остаток
                'total_inbound'    => (float) $r->total_inbound,
                'total_outbound'   => (float) $r->total_outbound,
                'remainder'        => (float) $closing,        // конечный остаток
                'cost_price'       => (float) $r->cost_price,
                'remainder_value'  => $remainderValue,
            ];
        });

        /* ── 4. группировка по складам ─────────────────────────────── */
        $rows = $detail
            ->groupBy('warehouse_id')
            ->map(function ($items) {

                $first = $items->first();

                return [
                    'warehouse_id'     => $first->warehouse_id,
                    'warehouse_name'   => $first->warehouse_name,

                    'opening'          => $items->sum('opening'),      // новый агрегат
                    'total_inbound'    => $items->sum('total_inbound'),
                    'total_outbound'   => $items->sum('total_outbound'),
                    'remainder'        => $items->sum('remainder'),
                    'remainder_value'  => $items->sum('remainder_value'),

                    'products' => $items->map(function ($r) {
                        return [
                            'product_id'       => $r->product_id,
                            'product_name'     => $r->product_name,

                            'opening'          => $r->opening,
                            'total_inbound'    => $r->total_inbound,
                            'total_outbound'   => $r->total_outbound,
                            'remainder'        => $r->remainder,
                            'cost_price'       => $r->cost_price,
                            'remainder_value'  => $r->remainder_value,
                        ];
                    })->values(),
                ];
            })
            ->values();

        /* ── 5. ответ ──────────────────────────────────────────────── */
        return response()->json([
            'date_from' => $from,
            'date_to'   => $to,
            'rows'      => $rows,
        ], 200);
    }

    /**
     * Отчет по долгам (Debts)
     */
    // public function debtsReport(Request $request)
    // {
    //     // 1) Validate or default the date inputs
    //     //    'nullable|date' means each date can be empty or a valid date
    //     $validated = $request->validate([
    //         'from_date' => 'nullable|date',
    //         'to_date'   => 'nullable|date',
    //     ]);

    //     // Extract them; they might be null if not provided
    //     $fromDate = $validated['from_date'] ?? null;
    //     $toDate   = $validated['to_date']   ?? null;

    //     // 2) Find all users with role=client
    //     $clients = User::whereHas('roles', function ($q) {
    //         $q->where('name', 'client');
    //     })->get();

    //     $results = [];

    //     foreach ($clients as $client) {
    //         // -----------------------------------------------------
    //         // A) OPENING BALANCE (Before $fromDate)
    //         //    If no $fromDate given, we skip or treat opening as 0
    //         // -----------------------------------------------------

    //         // 1) Opening "Incoming" (FinancialOrder) *before* fromDate
    //         $openingInQuery = FinancialOrder::where('user_id', $client->id);

    //         // Only apply the < fromDate filter if we actually have a fromDate
    //         if ($fromDate) {
    //             $openingInQuery->whereDate('date_of_check', '<', $fromDate);
    //         }
    //         // If $fromDate is null, we skip and get 0 or entire sum forever
    //         // (You can decide if that’s correct or if you want 0)
    //         $openingIn = $fromDate ? $openingInQuery->sum('summary_cash') : 0;

    //         // 2) Opening "Outgoing" (Documents of type="Продажа") *before* fromDate
    //         $openingOutDocs = Document::whereHas('documentType', function ($q) {
    //                 $q->where('name', 'Продажа');
    //             })
    //             ->where('client_id', $client->id);

    //         if ($fromDate) {
    //             $openingOutDocs->whereDate('document_date', '<', $fromDate);
    //         }
    //         $openingOutDocs = $fromDate
    //             ? $openingOutDocs->with('documentItems')->get()
    //             : collect([]); // if no $fromDate => treat as no opening docs

    //         $openingOut = $openingOutDocs->sum(function ($doc) {
    //             return $doc->documentItems->sum('total_sum');
    //         });

    //         // Opening balance = sum(incoming before fromDate) - sum(outgoing before fromDate)
    //         $openingBalance = $openingIn - $openingOut;


    //         // -----------------------------------------------------
    //         // B) INCOMING (in [fromDate, toDate])
    //         // -----------------------------------------------------
    //         $incomingQuery = FinancialOrder::where('user_id', $client->id);

    //         // If both fromDate & toDate are provided → whereBetween
    //         if ($fromDate && $toDate) {
    //             $incomingQuery->whereBetween('date_of_check', [$fromDate, $toDate]);
    //         } elseif ($fromDate) {
    //             $incomingQuery->whereDate('date_of_check', '>=', $fromDate);
    //         } elseif ($toDate) {
    //             $incomingQuery->whereDate('date_of_check', '<=', $toDate);
    //         }
    //         $incoming = $incomingQuery->sum('summary_cash');


    //         // -----------------------------------------------------
    //         // C) OUTGOING (in [fromDate, toDate]) for docs type="Продажа"
    //         // -----------------------------------------------------
    //         $outgoingDocsQuery = Document::whereHas('documentType', function ($q) {
    //                 $q->where('name', 'Продажа');
    //             })
    //             ->where('client_id', $client->id)
    //             ->with('documentItems');

    //         if ($fromDate && $toDate) {
    //             $outgoingDocsQuery->whereBetween('document_date', [$fromDate, $toDate]);
    //         } elseif ($fromDate) {
    //             $outgoingDocsQuery->whereDate('document_date', '>=', $fromDate);
    //         } elseif ($toDate) {
    //             $outgoingDocsQuery->whereDate('document_date', '<=', $toDate);
    //         }

    //         $outgoingDocs = $outgoingDocsQuery->get();
    //         $outgoing = $outgoingDocs->sum(function ($doc) {
    //             return $doc->documentItems->sum('total_sum');
    //         });


    //         // -----------------------------------------------------
    //         // D) CLOSING BALANCE = opening + incoming - outgoing
    //         // -----------------------------------------------------
    //         $closingBalance = $openingBalance + $incoming - $outgoing;


    //         // -----------------------------------------------------
    //         // E) Build array row for this client
    //         // -----------------------------------------------------
    //         $results[] = [
    //             'client_name'     => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
    //             'opening_balance' => $openingBalance,
    //             'incoming'        => $incoming,
    //             'outgoing'        => $outgoing,
    //             'closing_balance' => $closingBalance,
    //         ];
    //     }

    //     // 3) Return JSON so the front-end can easily render it
    //     return response()->json($results);
    // }


    public function debtsReport(Request $request): JsonResponse
{
    /* ───── 1. normalise inputs ───── */
    $from = $request->query('date_from');
    $to   = $request->query('date_to');
    $cli  = $request->query('client');
    $flow = $request->query('flow', 'all');          // all | income | expense

    foreach (['from','to','cli','flow'] as $v) {
        if ($$v === '' || $$v === 'null') $$v = null;
    }
    if (!in_array($flow, ['income','expense','all'], true)) {
        $flow = 'all';
    }

    $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::parse('1970-01-01');
    $to   = $to   ? Carbon::parse($to  )->endOfDay()   : now()->endOfDay();

    /* ───── 2. load client list (role=client) ───── */
    $clients = User::query()
        ->when($cli, function($q) use($cli) {
            if (Str::isUuid($cli)) {
                $q->where('id', $cli);
            } else {
                $q->whereRaw(
                    "LOWER(CONCAT_WS(' ', first_name, last_name, surname)) LIKE ?",
                    ['%'.mb_strtolower($cli).'%']
                );
            }
        })
        ->whereHas('roles', fn($r)=>$r->where('name','client'))
        ->select('id','first_name','last_name','surname')
        ->get();

    if ($clients->isEmpty()) {
        return response()->json([
            'date_from' => $from->toDateString(),
            'date_to'   => $to->toDateString(),
            'flow'      => $flow ?? 'all',
            'rows'      => [],
        ], 200);
    }

    $clientIds = $clients->pluck('id');

    /* ───── 3. opening balance (до $from) ───── */
    $incomeBefore = FinancialOrder::where('type','income')
        ->whereIn('user_id',$clientIds)
        ->whereDate('date_of_check','<',$from)
        ->groupBy('user_id')
        ->selectRaw('user_id, SUM(summary_cash) as sum')
        ->pluck('sum','user_id');                         // [user_id=>sum]

    $salesBefore  = DB::table('documents AS d')
        ->join('document_types AS dt','dt.id','=','d.document_type_id')
        ->join('document_items AS di','di.document_id','=','d.id')
        ->where('dt.code','sale')
        ->whereIn('d.client_id',$clientIds)
        ->whereDate('d.document_date','<',$from)
        ->groupBy('d.client_id')
        ->selectRaw('d.client_id, SUM(di.total_sum) AS sum')
        ->pluck('sum','client_id');                       // [client_id=>sum]

    /* ───── 4. flows inside period ───── */
    $incomeNow = FinancialOrder::where('type','income')
        ->whereIn('user_id',$clientIds)
        ->whereBetween('date_of_check',[$from,$to])
        ->groupBy('user_id')
        ->selectRaw('user_id, SUM(summary_cash) as sum')
        ->pluck('sum','user_id');                         // [user_id=>sum]

    $salesNow  = DB::table('documents AS d')
        ->join('document_types AS dt','dt.id','=','d.document_type_id')
        ->join('document_items AS di','di.document_id','=','d.id')
        ->where('dt.code','sale')
        ->whereIn('d.client_id',$clientIds)
        ->whereBetween('d.document_date',[$from,$to])
        ->groupBy('d.client_id')
        ->selectRaw('d.client_id, SUM(di.total_sum) AS sum')
        ->pluck('sum','client_id');                       // [client_id=>sum]

    /* ───── 5. build rows ───── */
    $rows = $clients->map(function($c) use ($incomeBefore,$salesBefore,$incomeNow,$salesNow,$flow) {

        $opening  = ($incomeBefore[$c->id] ?? 0) - ($salesBefore[$c->id] ?? 0);

        $incoming = $incomeNow[$c->id] ?? 0;   // платежи клиента
        $outgoing = $salesNow [$c->id] ?? 0;   // отгружено товара

        /* apply flow filter */
        $inc = ($flow === 'expense') ? 0 : $incoming;
        $out = ($flow === 'income' ) ? 0 : $outgoing;

        return [
            'client' => [
                'id'   => $c->id,
                'name' => trim("{$c->first_name} {$c->last_name} {$c->surname}") ?: '—',
            ],
            'opening'  => round($opening ,2),
            'incoming' => round($inc     ,2),
            'outgoing' => round($out     ,2),
            'closing'  => round($opening + $inc - $out ,2),
        ];
    })->values();

    return response()->json([
        'date_from' => $from->toDateString(),
        'date_to'   => $to  ->toDateString(),
        'flow'      => $flow ?? 'all',
        'rows'      => $rows,
    ], 200);
}
    /**
     * Отчет по продажам (Sales)
     */

     public function getSalesReport(Request $request): JsonResponse
{
    /* ───── 1. «Нормализуем» вход ───── */
    $from  = $request->input('date_from');
    $to    = $request->input('date_to');
    $buyer = $request->input('client');
    $prod  = $request->input('product');

    // превращаем 'null' или '' в null
    foreach (['from','to','buyer','prod'] as $v) {
        if ($$v === 'null' || $$v === '') {
            $$v = null;
        }
    }

    /* ───── 2. Загружаем только документы-sale ───── */
    $docs = Document::whereHas('documentType', function ($q) {
                $q->where('code', 'sale');
            })
            ->with([
                'client:id,first_name,last_name,surname',
                'documentItems.product:id,name'
            ])

            // даты
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->whereBetween('document_date', [$from, $to]);
            })
            ->when($from && !$to, function ($q) use ($from) {
                $q->whereDate('document_date', '>=', $from);
            })
            ->when(!$from && $to, function ($q) use ($to) {
                $q->whereDate('document_date', '<=', $to);
            })

            // клиент
            ->when($buyer, function ($q) use ($buyer) {
                $q->whereHas('client', function ($s) use ($buyer) {
                    Str::isUuid($buyer)
                        ? $s->where('id', $buyer)
                        : $s->whereRaw(
                            "LOWER(CONCAT_WS(' ', first_name, last_name, surname)) LIKE ?",
                            ['%'.mb_strtolower($buyer).'%']
                          );
                });
            })

            // товар
            ->when($prod, function ($q) use ($prod) {
                $q->whereHas('documentItems.product', function ($s) use ($prod) {
                    Str::isUuid($prod)
                        ? $s->where('id', $prod)
                        : $s->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($prod).'%']);
                });
            })
            ->get();

    /* ───── 3. Формируем строки отчёта ───── */
    $rows = [];

    foreach ($docs as $doc) {
        $warehouseId = $doc->from_warehouse_id;        // склад списания

        foreach ($doc->documentItems as $item) {

            /* суммы продажи */
            $qty      = (float) $item->quantity;
            $saleAmt  = $item->price * $qty;

            /* себестоимость */
            $costPrice = 0;
            if ($warehouseId) {
                $wi = WarehouseItem::where('warehouse_id', $warehouseId)
                    ->where('product_subcard_id', $item->product_subcard_id)
                    ->first();
                $costPrice = $wi ? (float) $wi->cost_price : 0;
            }
            $costAmt = $costPrice * $qty;

            /* клиент */
            $cl   = $doc->client;                                // может быть null
            $name = trim(
                        ($cl->first_name ?? '').' '.
                        ($cl->last_name  ?? '').' '.
                        ($cl->surname    ?? '')
                    ) ?: '—';

            /* строка */
            $rows[] = [
                'client' => [
                    'id'   => $cl->id   ?? null,
                    'name' => $name,
                ],
                'product' => [
                    'id'   => $item->product->id   ?? null,
                    'name' => $item->product->name ?? '—',
                ],
                'document_date' => Carbon::parse($doc->document_date)->toDateString(),
                'quantity'      => $qty,
                'sale_amount'   => round($saleAmt , 2),
                'cost_amount'   => round($costAmt , 2),
                'profit'        => round($saleAmt - $costAmt, 2),
            ];
        }
    }

    return response()->json($rows, 200);
}



     public function cash_report(Request $request)
{
    /* ───── 1. нормализация входа ───── */
    $from = self::normalizeDate($request->input('date_from'));
    $to   = self::normalizeDate($request->input('date_to'));
    $cbx  = self::normalizeText($request->input('cashbox'));   // касса
    $elt  = self::normalizeText($request->input('element'));   // статья

    /* ───── 2. выборка ордеров ───── */
    $orders = FinancialOrder::with(['adminCash:id,name','financialElement:id,name'])
        ->when($from, fn($q)=>$q->whereDate('date_of_check','>=',$from))
        ->when($to  , fn($q)=>$q->whereDate('date_of_check','<=',$to  ))
        ->when($cbx , fn($q)=>$q->whereHas('adminCash',        fn($s)=>self::filterName($s,$cbx)))
        ->when($elt , fn($q)=>$q->whereHas('financialElement', fn($s)=>self::filterName($s,$elt)))
        ->get();

    /* ───── 3. «начальный остаток» ───── */
    $prevBalances = [];
    if ($from) {
        $before = FinancialOrder::with('adminCash:id,name')
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

    /* ───── 4. агрегируем отчёт ───── */
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

    return response()->json($report,200);
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
