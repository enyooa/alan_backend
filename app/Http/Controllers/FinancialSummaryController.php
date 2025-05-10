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

    public function summary(Request $r)
{
    $by = $r->input('by', 'day');               // day | week | month | year

    switch ($by) {

        /* =============== ДЕНЬ (24 часа) =============== */
        case 'day':
            $pivot  = Carbon::parse($r->input('date', now()));
            $from   = $pivot->copy()->startOfDay();
            $to     = $pivot->copy()->endOfDay();

            /*  group by HOUR(date_of_check)  */
            $rows = $this->fetchGrouped($from, $to, 'HOUR(date_of_check)');
            [$details, $total] = $this->spreadHours($rows);
            break;

        /* =============== НЕДЕЛЯ (Пн-Вс) =============== */
        case 'week':
            $pivot = Carbon::parse($r->input('date', now()));
            $from  = $pivot->copy()->startOfWeek(Carbon::MONDAY);
            $to    = $pivot->copy()->endOfWeek(Carbon::SUNDAY);

            /*  group by DATE()  */
            $rows = $this->fetchGrouped($from, $to, 'DATE(date_of_check)');
            [$details, $total] = $this->spreadDays($from, 7, $rows);
            break;

        /* =============== МЕСЯЦ (1-31) =============== */
        case 'month':
            $y     = $r->input('year',  now()->year);
            $m     = $r->input('month', now()->month);
            $from  = Carbon::create($y, $m, 1)->startOfDay();
            $to    = $from->copy()->endOfMonth();

            $rows  = $this->fetchGrouped($from, $to, 'DATE(date_of_check)');
            [$details, $total] = $this->spreadMonthDays($from, $rows);
            break;

        /* =============== ГОД (Янв-Дек) =============== */
        case 'year':
            $y     = $r->input('year', now()->year);
            $from  = Carbon::create($y, 1, 1)->startOfDay();
            $to    = $from->copy()->endOfYear();

            $rows  = $this->fetchGrouped($from, $to, 'MONTH(date_of_check)');
            [$details, $total] = $this->spreadMonths($rows);
            break;

        default:
            return response()->json(['error'=>'Invalid "by" parameter'], 422);
    }

    return response()->json([
        'scope'   => $by,
        'range'   => ['from'=>$from->toDateString(), 'to'=>$to->toDateString()],
        'details' => $details,
        'total'   => $total,
    ]);
}

/* ---------- helpers ------------------------------------------------- */

/** SELECT … GROUP BY $expr  →  вернёт [k => revenue] */
private function fetchGrouped(Carbon $from, Carbon $to, string $expr): array
{
    return FinancialOrder::selectRaw("$expr as k, SUM(summary_cash) as revenue")
        ->whereBetween('date_of_check', [$from, $to])
        ->groupBy('k')
        ->pluck('revenue', 'k')
        ->toArray();                 // ['2025-04-15'=>123 , …]  или  [0=>55,1=>77 …]
}

/* ---------- раскладки ---------- */

/* 24 часа */
private function spreadHours(array $rows): array
{
    $details = []; $total = 0;
    for ($h = 0; $h < 24; $h++) {
        $label = str_pad($h, 2, '0', STR_PAD_LEFT);   // "00"…"23"
        $val   = (float) ($rows[$h] ?? 0);
        $total += $val;
        $details[] = ['value'=>$label, 'revenue'=>$val];
    }
    return [$details, $total];
}

/* 7 дней начиная с $from (Пн-Вс) */
private function spreadDays(Carbon $from, int $days, array $rows): array
{
    static $ru = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
    $details = []; $total = 0;

    for ($i = 0; $i < $days; $i++) {
        $d      = $from->copy()->addDays($i);
        $label  = $ru[$d->dayOfWeekIso % 7];          // Пн-Вс
        $val    = (float) ($rows[$d->toDateString()] ?? 0);
        $total += $val;
        $details[] = ['value'=>$label, 'revenue'=>$val];
    }
    return [$details, $total];
}

/* дни месяца (1-31, но строкой "01"…"31") */
private function spreadMonthDays(Carbon $start, array $rows): array
{
    $end     = $start->copy()->endOfMonth();
    $details = []; $total = 0;

    for ($d = $start->copy(); $d <= $end; $d->addDay()) {
        $label = str_pad($d->day, 2, '0', STR_PAD_LEFT);
        $val   = (float) ($rows[$d->toDateString()] ?? 0);
        $total += $val;
        $details[] = ['value'=>$label, 'revenue'=>$val];
    }
    return [$details, $total];
}

/* Янв-Дек */
private function spreadMonths(array $rows): array
{
    static $ru = ['','Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'];
    $details = []; $total = 0;

    for ($m = 1; $m <= 12; $m++) {
        $val   = (float) ($rows[$m] ?? 0);
        $total += $val;
        $details[] = ['value'=>$ru[$m], 'revenue'=>$val];
    }
    return [$details, $total];
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

    /* ---------- сгруппируем в PHP ---------- */
    $data = [];

    foreach ($baseQuery->get() as $order) {

        // безопасно берём имена, даже если relation = null
        $cashbox = optional($order->adminCash)->name ?? '—';
        $element = optional($order->financialElement)->name ?? '—';

        // контейнер-касса
        if (! isset($data[$cashbox])) {
            $data[$cashbox] = [
                'cashbox' => $cashbox,
                'start'   => 0,
                'income'  => 0,
                'expense' => 0,
                'end'     => 0,
                'rows'    => [],
            ];
        }

        $rowIncome  = $order->type === 'income'  ? (float) $order->summary_cash : 0;
        $rowExpense = $order->type === 'expense' ? (float) $order->summary_cash : 0;

        // суммируем по кассе
        $data[$cashbox]['income']  += $rowIncome;
        $data[$cashbox]['expense'] += $rowExpense;
        $data[$cashbox]['end']      = $data[$cashbox]['start']
                                    + $data[$cashbox]['income']
                                    - $data[$cashbox]['expense'];

        // суммируем по элементу внутри кассы
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

    /* ---------- финальный отчёт ---------- */
    $report = collect($data)
        ->map(function ($cashbox) {
            $cashbox['rows'] = array_values($cashbox['rows']); // переиндексация
            return $cashbox;
        })
        ->values();

    return response()->json($report, 200);
}

}
