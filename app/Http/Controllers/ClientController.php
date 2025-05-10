<?php

namespace App\Http\Controllers;

use App\Models\CourierDocument;
use App\Models\Document;
use App\Models\FinancialOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductCard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StatusDoc;
class ClientController extends Controller
{

    public function getAllProductData()
    {
        try {
            $productCards = ProductCard::with(['subCards.sales'])->get();

            $data = $productCards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'name_of_products' => $card->name_of_products,
                    'description' => $card->description,
                    'photo_url' => $card->photo_url,
                    'subcards' => $card->subCards->map(function ($subCard) {
                        return [
                            'id' => $subCard->id,
                            'name' => $subCard->name,
                            'brutto' => $subCard->brutto,
                            'netto' => $subCard->netto,
                            'sales' => $subCard->sales->map(function ($sale) {
                                return [
                                    'id' => $sale->id,
                                    'price' => $sale->price,
                                    'quantity' => $sale->quantity,
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product data', 'error' => $e->getMessage()], 500);
        }
    }


    public function getClientOrders()
{
    $user = Auth::user();                            // текущий клиент

    /* uuid статуса «ожидание» */
    $waitingStatusId = StatusDoc::where('name', 'ожидание')->value('id');
    if (!$waitingStatusId) {
        return [
            'success' => false,
            'error'   => 'Статус «ожидание» не найден в status_docs',
        ];
    }

    /* заказы клиента + статус + организация */
    $orders = Order::where('user_id', $user->id)
        ->where('status_id', $waitingStatusId)
        ->with([
            'statusDoc:id,name',                       // объект статуса
            'organization:id,name',                    // 👈 объект организации у заказа
            'orderItems.productSubCard.productCard',   // позиции и товары
        ])
        ->orderByDesc('created_at')
        ->get();

    /* словарь всех статусов */
    $statuses = StatusDoc::pluck('name', 'id');       // { id: name }

    /* обычный массив → Laravel сам сделает JSON */
    return [
        'success'  => true,
        'orders'   => $orders,        // у каждого order есть .organization
        'statuses' => $statuses,
    ];
}


    public function report_debs(Request $request): JsonResponse
    {
        /* 1.  Пользователь и период ---------------------------------- */
        $uid  = Auth::id();
        $from = Carbon::parse($request->query('date_start', '1970-01-01'))->startOfDay();
        $to   = Carbon::parse($request->query('end_date',   now()))      ->endOfDay();

        /* 2.  Приход: подтверждённые документы клиента ---------------- */
        $incomeRows = Document::withSum(       // Laravel 8+
                            'documentItems as doc_sum', 'total_sum'
                        )
            ->where('status',    'confirmed')
            ->where('client_id', $uid)
            ->whereBetween('document_date', [$from, $to])
            ->orderBy('document_date')
            ->get(['id','document_date']);     // только нужные поля

        $incomeTotal = $incomeRows->sum('doc_sum');

        /* 3.  Расход: платежи клиента -------------------------------- */
        $expenseRows = FinancialOrder::where('user_id', $uid)
            ->whereBetween('date_of_check', [$from, $to])
            ->orderBy('date_of_check')
            ->get([
                'id            as payment_id',
                'date_of_check as date',
                'summary_cash',
            ]);

        $expenseTotal = $expenseRows->sum('summary_cash');

        /* 4.  Балансы ------------------------------------------------- */
        $opening  = 0.0;
        $closing  = $opening + $incomeTotal - $expenseTotal;

        /* 5.  Ответ --------------------------------------------------- */
        return response()->json([
            'date_from'       => $from->toDateString(),
            'date_to'         => $to->toDateString(),

            'opening_balance' => (float) $opening,
            'income_total'    => (float) $incomeTotal,
            'expense_total'   => (float) $expenseTotal,
            'closing_balance' => (float) $closing,

            /* списки без вложенных строк */
            'income_rows'     => $incomeRows->map(fn($d) => [
                                    'document_id'   => $d->id,
                                    'document_date' => $d->document_date,
                                    'doc_sum'       => (float) $d->doc_sum,
                                ]),
            'expense_rows'    => $expenseRows->map(fn($p) => [
                                    'payment_id'    => $p->payment_id,
                                    'date'          => $p->date,
                                    'summary_cash'  => (float) $p->summary_cash,
                                ]),
        ]);
    }

}
