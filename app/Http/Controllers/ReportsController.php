<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Expense;
use App\Models\FinancialOrder;
use App\Models\ProductSubCard;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
    public function getStorageReport(Request $request)
    {
        // Получаем даты из запроса (пример: ?date_from=2025-01-01&date_to=2025-01-31)
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // (Необязательно) Получаем ID-шники типов документов по коду,
        // если где-то ещё потребуется.
        $typeIncome   = DocumentType::where('code', 'income')->value('id');
        $typeTransfer = DocumentType::where('code', 'transfer')->value('id');
        $typeSale     = DocumentType::where('code', 'sale')->value('id');
        $typeWriteOff = DocumentType::where('code', 'write_off')->value('id');

        // «Основной» отчёт: приход/расход/остаток по каждому складу и товару
        $report = DB::table('warehouses AS wh')
            ->join('warehouse_items AS wi', 'wi.warehouse_id', '=', 'wh.id')
            ->join('product_sub_cards AS psc', 'psc.id', '=', 'wi.product_subcard_id')
            // Документы (leftJoin)
            ->leftJoin('documents AS d', function($join) {
                $join->on('d.to_warehouse_id', '=', 'wh.id')
                     ->orOn('d.from_warehouse_id', '=', 'wh.id');
            })
            ->leftJoin('document_types AS dt', 'dt.id', '=', 'd.document_type_id')
            ->leftJoin('document_items AS di', function($join) {
                $join->on('di.document_id', '=', 'd.id');
                $join->on('di.product_subcard_id', '=', 'psc.id');
            })
            ->select(
                'wh.id AS warehouse_id',
                'wh.name AS warehouse_name',
                'psc.id AS product_id',
                'psc.name AS product_name',
                'wi.quantity AS current_quantity',    // Текущий остаток (warehouse_items)
                'wi.cost_price AS current_cost_price',

                // Приход
                DB::raw("
                    SUM(
                        CASE
                          WHEN (dt.code = 'income' AND d.to_warehouse_id = wh.id)
                               OR (dt.code = 'transfer' AND d.to_warehouse_id = wh.id)
                          THEN di.quantity
                          ELSE 0
                        END
                    ) AS total_inbound
                "),
                // Расход
                DB::raw("
                    SUM(
                        CASE
                          WHEN (dt.code = 'sale'       AND d.from_warehouse_id = wh.id)
                               OR (dt.code = 'write_off' AND d.from_warehouse_id = wh.id)
                               OR (dt.code = 'transfer'  AND d.from_warehouse_id = wh.id)
                          THEN di.quantity
                          ELSE 0
                        END
                    ) AS total_outbound
                ")
            )
            // Фильтруем документы по дате, если пользователь указал date_from и date_to
            ->when($dateFrom && $dateTo, function($query) use ($dateFrom, $dateTo) {
                return $query->whereBetween('d.document_date', [$dateFrom, $dateTo]);
            })
            ->groupBy('wh.id', 'psc.id', 'wi.quantity', 'wi.cost_price', 'wh.name', 'psc.name')
            ->orderBy('wh.id')
            ->orderBy('psc.id')
            ->get();

        // Превращаем результат в коллекцию, пересчитываем остаток/сумму остатка
        $report->transform(function ($row) {
            $inbound       = $row->total_inbound ?? 0;
            $outbound      = $row->total_outbound ?? 0;
            // Либо берем "current_quantity", либо считаем "inbound - outbound":
            $currentStock  = $inbound - $outbound;
            $costPrice     = $row->current_cost_price ?? 0;
            $value         = $currentStock * $costPrice;

            // Добавим поля remainder и remainder_value,
            // чтобы на фронте их легко вывести
            $row->remainder        = $currentStock;
            $row->cost_price       = $costPrice;
            $row->remainder_value  = $value;

            return $row;
        });

        // !!! Важно: возвращаем JSON-массив, чтобы Vue могла его прочитать !!!
        return response()->json($report);
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

    public function debtsReport(Request $request)
{
    $dateFrom = $request->input('date_from');
    $dateTo   = $request->input('date_to');

    $finalRows = [];

    // --------------------------------------------------
    // PART A: INCOME DOCS for each provider
    // plus OUTGOING from financial_orders.type='expense'
    // --------------------------------------------------
    // 1) Query documents with docType->code='income'
    $incomeDocsQuery = Document::whereHas('documentType', function($q) {
            $q->where('code', 'income');
        })
        ->with('provider', 'documentItems');
        // we remove ->expenses here since we’ll get “expense” from financial_orders

    // If date range
    if ($dateFrom && $dateTo) {
        $incomeDocsQuery->whereBetween('document_date', [$dateFrom, $dateTo]);
    }
    $incomeDocs = $incomeDocsQuery->get();

    // Group by provider_id
    $grouped = $incomeDocs->groupBy('provider_id');

    // 2) Add a top-level "group" row to label this section
    $finalRows[] = [
        'row_type' => 'group',
        'label'    => 'Документы (income) + Расход (financial_orders.type=expense)',
        'name'     => null,
        'incoming' => null,
        'outgoing' => null,
        'balance'  => null,
    ];

    foreach ($grouped as $providerId => $docs) {
        // Provider name
        $providerName = $docs->first()->provider
            ? $docs->first()->provider->name
            : "Без поставщика";

        // Sum the "incoming" = sum of docItems for these docs
        $providerIncoming = 0;
        foreach ($docs as $doc) {
            $providerIncoming += $doc->documentItems->sum('total_sum');
        }

        // Now sum "expense" from financial_orders for this provider
        // (Optionally filter by date_of_check if date range is set)
        $expenseQuery = FinancialOrder::where('provider_id', $providerId)
            ->where('type', 'expense');
        if ($dateFrom && $dateTo) {
            $expenseQuery->whereBetween('date_of_check', [$dateFrom, $dateTo]);
        }
        $providerExpense = (float) $expenseQuery->sum('summary_cash');

        // The "provider" row
        $finalRows[] = [
            'row_type' => 'provider',
            'name'     => $providerName,
            'incoming' => $providerIncoming,
            'outgoing' => $providerExpense,
            'balance'  => $providerIncoming - $providerExpense,
        ];

        // Then each doc row
        foreach ($docs as $doc) {
            $docTotal = $doc->documentItems->sum('total_sum');
            $finalRows[] = [
                'row_type' => 'doc',
                'name'     => "Документ #{$doc->id} ({$doc->document_date})",
                'incoming' => $docTotal,
                'outgoing' => 0,
                'balance'  => $docTotal // or 0
            ];
        }
    }

    // --------------------------------------------------
    // PART B: CLIENT DEBTS (unchanged)
    // --------------------------------------------------
    // Add "group" label row
    $finalRows[] = [
        'row_type' => 'group',
        'label'    => 'Отчет по долгам (client)',
        'name'     => null,
        'incoming' => null,
        'outgoing' => null,
        'balance'  => null,
    ];

    // Your existing subquery logic for clients
    $clientIds = DB::table('role_user AS ru')
        ->join('roles AS r', 'r.id', '=', 'ru.role_id')
        ->join('users AS u', 'u.id', '=', 'ru.user_id')
        ->where('r.name', 'client')
        ->pluck('u.id')
        ->toArray();

    $subIncomingFilter = '';
    $subOutgoingFilter = '';
    if ($dateFrom && $dateTo) {
        $subIncomingFilter = " AND fo.date_of_check BETWEEN '{$dateFrom}' AND '{$dateTo}' ";
        $subOutgoingFilter = " AND d.document_date BETWEEN '{$dateFrom}' AND '{$dateTo}' ";
    }

    $debtsData = DB::table('users AS c')
        ->select(
            'c.id AS client_id',
            DB::raw("CONCAT(c.first_name, ' ', c.last_name) AS client_name"),
            DB::raw("
                COALESCE((
                    SELECT SUM(fo.summary_cash)
                    FROM financial_orders fo
                    WHERE fo.user_id = c.id
                    $subIncomingFilter
                ), 0) AS total_incoming
            "),
            DB::raw("
                COALESCE((
                    SELECT SUM(di.total_sum)
                    FROM documents d
                    JOIN document_types dt ON dt.id = d.document_type_id
                    JOIN document_items di ON di.document_id = d.id
                    WHERE d.client_id = c.id
                      AND dt.code = 'sale'
                    $subOutgoingFilter
                ), 0) AS total_outgoing
            ")
        )
        ->whereIn('c.id', $clientIds)
        ->orderBy('c.id')
        ->get();

    $debtsData->transform(function ($row) {
        $incoming = (float) $row->total_incoming;
        $outgoing = (float) $row->total_outgoing;
        $row->balance = $incoming - $outgoing;
        return $row;
    });

    // Add row_type='client' for each client debt
    foreach ($debtsData as $cd) {
        $finalRows[] = [
            'row_type' => 'client',
            'name'     => $cd->client_name,
            'incoming' => (float)$cd->total_incoming,
            'outgoing' => (float)$cd->total_outgoing,
            'balance'  => (float)$cd->balance,
        ];
    }

    return response()->json($finalRows);
}


    /**
     * Отчет по продажам (Sales)
     */
    public function getSalesReport(Request $request)
    {
        $dateFrom = $request->input('start_date');
        $dateTo   = $request->input('end_date');

        // 1) Query only documents of type 'sale' (assuming docType code='sale').
        $query = Document::whereHas('documentType', function($q) {
            $q->where('code', 'sale');
        })->with('documentItems.product');

        // 2) Filter by date range if provided
        if ($dateFrom && $dateTo) {
            $query->whereBetween('document_date', [$dateFrom, $dateTo]);
        }

        // 3) Get all sale documents (with items)
        $documents = $query->get();

        $reportRows = [];

        // 4) For each DocumentItem, find cost_price from warehouse_items
        foreach ($documents as $doc) {
            // The warehouse from which items left
            $warehouseId = $doc->from_warehouse_id;

            foreach ($doc->documentItems as $item) {
                $quantity = $item->quantity;
                $netto    = $item->netto; // if you want to base saleAmount on nett weight

                // (A) saleAmount: For example, price * netto (or price * quantity, or item->total_sum)
                $saleAmount = $item->price * $netto;

                // (B) Find matching WarehouseItem to get cost_price
                //     (Adjust logic if you also track unit_measurement, etc.)
                $warehouseItem = null;
                if ($warehouseId) {
                    $warehouseItem = WarehouseItem::where('warehouse_id', $warehouseId)
                        ->where('product_subcard_id', $item->product_subcard_id)
                        // ->where('unit_measurement', $item->unit_measurement) // if needed
                        ->first();
                }

                // If not found, fallback cost_price = 0
                $costPricePerUnit = $warehouseItem ? ($warehouseItem->cost_price ?? 0) : 0;

                // If your cost_price is per *1 piece*, multiply by $quantity
                // If your cost_price is per *1 kg*, multiply by $netto (if that's the net weight in kg)
                // Decide which is correct in your scenario:
                $costAmount = $costPricePerUnit * $quantity;
                // or, if cost_price was per kg, do:
                // $costAmount = $costPricePerUnit * $netto;

                // (C) Compute profit
                $profit = $saleAmount - $costAmount;

                // (D) Build row
                $productName = $item->product ? $item->product->name : 'Unknown';
                // If your 'document_date' is cast to Date or a Carbon, you can format it. Otherwise:
                $docDate = $doc->document_date; // or strval($doc->document_date);

                $reportRows[] = [
                    'product_name' => $productName,
                    'quantity'     => $quantity,
                    'sale_amount'  => $saleAmount,
                    'cost_amount'  => $costAmount,
                    'profit'       => $profit,
                    'document_date'=> $docDate,  // if you want the date in the report
                ];
            }
        }

        // 5) Return JSON
        return response()->json($reportRows);
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
