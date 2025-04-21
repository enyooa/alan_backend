<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialOrder;
use Carbon\Carbon;

class FinancialSummaryController extends Controller
{
    /**
     * /api/financial-summary/day?date=2025-04-19
     */
    public function day(Request $request)
    {
        $date = Carbon::parse($request->input('date', now()->toDateString()));

        $revenue = FinancialOrder::whereDate('date_of_check', $date)
                     ->sum('summary_cash');

        return response()->json([
            'period'  => $date->toDateString(),
            'revenue' => (float) $revenue        // выручка
        ]);
    }

    /**
     * /api/financial-summary/week?date=2025-04-19
     * Вернёт 7‑элементный массив «день → выручка» + итог.
     */
    public function week(Request $request)
    {
        $pivot = Carbon::parse($request->input('date', now()));
        $start = $pivot->copy()->startOfWeek(Carbon::MONDAY);
        $end   = $pivot->copy()->endOfWeek(Carbon::SUNDAY);

        $rows = FinancialOrder::selectRaw('DATE(date_of_check) as d, SUM(summary_cash) as revenue')
                  ->whereBetween('date_of_check', [$start, $end])
                  ->groupBy('d')
                  ->orderBy('d')
                  ->get()
                  ->keyBy('d');                               // удобнее искать по дате

        $result = [];
        $total  = 0;
        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $value   = (float) ($rows[$day->toDateString()]->revenue ?? 0);
            $total  += $value;
            $result[] = [
                'date'    => $day->toDateString(),
                'revenue' => $value
            ];
        }

        return response()->json([
            'range'   => [$start->toDateString(), $end->toDateString()],
            'details' => $result,    // для графика
            'total'   => $total
        ]);
    }

    /**
     * /api/financial-summary/month?year=2025&month=04
     * Вернёт список «день → выручка» и итог.
     */
    public function month(Request $request)
    {
        $year  = $request->input('year',  now()->year);
        $month = $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        $rows = FinancialOrder::selectRaw('DATE(date_of_check) as d, SUM(summary_cash) as revenue')
                  ->whereBetween('date_of_check', [$start, $end])
                  ->groupBy('d')
                  ->orderBy('d')
                  ->get()
                  ->keyBy('d');

        $result = [];
        $total  = 0;
        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $value   = (float) ($rows[$day->toDateString()]->revenue ?? 0);
            $total  += $value;
            $result[] = [
                'date'    => $day->toDateString(),
                'revenue' => $value
            ];
        }

        return response()->json([
            'range'   => [$start->toDateString(), $end->toDateString()],
            'details' => $result,
            'total'   => $total
        ]);
    }

    /**
     * /api/financial-summary/year?year=2025
     * Вернёт массив «месяц → выручка» и итог.
     */
    public function year(Request $request)
    {
        $year  = $request->input('year', now()->year);
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = $start->copy()->endOfYear();

        $rows = FinancialOrder::selectRaw('MONTH(date_of_check) as m, SUM(summary_cash) as revenue')
                  ->whereBetween('date_of_check', [$start, $end])
                  ->groupBy('m')
                  ->orderBy('m')
                  ->get()
                  ->keyBy('m');

        $result = [];
        $total  = 0;
        for ($m = 1; $m <= 12; $m++) {
            $value  = (float) ($rows[$m]->revenue ?? 0);
            $total += $value;
            $result[] = [
                'month'   => $m,               // 1‑12
                'revenue' => $value
            ];
        }

        return response()->json([
            'year'    => $year,
            'details' => $result,
            'total'   => $total
        ]);
    }


    public function index(Request $request)
    {
        /* ---------- входные фильтры ---------- */
        $from = $request->input('date_from');
        $to   = $request->input('date_to');

        /* ---------- основная выборка ---------- */
        $baseQuery = FinancialOrder::query()
            ->with(['adminCash:id,name', 'financialElement:id,name'])
            ->when(
                $from && $to,
                fn ($q) => $q->whereBetween('date_of_check', [$from, $to])
            )
            ->when(
                $request->filled('cashbox'),
                fn ($q) => $q->whereHas('adminCash', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%'.$request->cashbox.'%');
                })
            )
            ->when(
                $request->filled('element'),
                fn ($q) => $q->whereHas('financialElement', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%'.$request->element.'%');
                })
            );

        /* ---------- сгруппируем в PHP (проще для составного ответа) ---------- */
        $data  = [];
        foreach ($baseQuery->get() as $order) {

            $cashbox = $order->adminCash?->name ?: '—';
            $element = $order->financialElement?->name ?: '—';

            // контейнер кассы
            if (!isset($data[$cashbox])) {
                $data[$cashbox] = [
                    'cashbox' => $cashbox,
                    'start'   => 0,           // если нужен «начальный остаток» – см. ниже
                    'income'  => 0,
                    'expense' => 0,
                    'end'     => 0,
                    'rows'    => []
                ];
            }

            $rowIncome  = $order->type === 'income'  ? +$order->summary_cash : 0;
            $rowExpense = $order->type === 'expense' ? +$order->summary_cash : 0;

            // агрегаты кассы
            $data[$cashbox]['income']  += $rowIncome;
            $data[$cashbox]['expense'] += $rowExpense;
            $data[$cashbox]['end']      = $data[$cashbox]['start']
                                         + $data[$cashbox]['income']
                                         - $data[$cashbox]['expense'];

            // агрегаты строки‑детали
            if (! isset($data[$cashbox]['rows'][$element])) {
                $data[$cashbox]['rows'][$element] = [
                    'element' => $element,
                    'income'  => 0,
                    'expense' => 0,
                ];
            }
            $data[$cashbox]['rows'][$element]['income']  += $rowIncome;
            $data[$cashbox]['rows'][$element]['expense'] += $rowExpense;
        }

        /* ---------- финальное приведение ---------- */
        $report = collect($data)->map(function ($cashbox) {
            $cashbox['rows'] = array_values($cashbox['rows']);   // переиндексация
            return $cashbox;
        })->values();

        return response()->json($report, 200);
    }
}
