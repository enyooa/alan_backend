<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
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
    public function warehouseReport()
    {
        // Example: retrieve data from warehouse_reports table
        $data = DB::table('warehouse_reports')->get();

        return response()->json($data, 200);
    }

    /**
     * Отчет по долгам (Debts)
     */
    public function debtsReport()
    {
        // Example: retrieve data from debt_reports table
        $data = DB::table('debt_reports')->get();

        return response()->json($data, 200);
    }

    /**
     * Отчет по продажам (Sales)
     */
    public function salesReport()
    {
        // Example: retrieve data from sales_reports table
        $data = DB::table('sales_reports')->get();

        return response()->json($data, 200);
    }
}
