<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentType;
use App\Models\Order;

use App\Models\StatusDoc;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function assignPacker(Request $request, $orderId)
    {
        $validated = $request->validate([
            'packer_id' => 'required|exists:users,id',
        ]);

        $order = Order::findOrFail($orderId);
        $order->packer_id = $validated['packer_id'];
        $order->status = 'assigned_to_packer'; // Update the status
        $order->save();

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function getPackerOrders()
    {
        /* 1. Организация текущего юзера */
        $orgId = Auth::user()->organization_id;

        /* 2. Какие статусы показываем */
        $statuses = StatusDoc::pluck('name', 'id');   // id => name

        /* 3. Заказы по этим статусам */
        $orders = Order::with([
                        'orderProducts.productSubCard.productCard',
                        'packer:id,first_name,last_name,photo',
                        'courier:id,first_name,last_name,photo',
                        'client:id,first_name,last_name',
                        'statusDoc:id,name',
                    ])
                    ->where('organization_id', $orgId)
                    ->whereIn('status_id', $statuses->keys())
                    ->orderByDesc('created_at')
                    ->get();

        /* 4. Просто вернём массив — Laravel сам сделает JSON */
        return [
            'success'  => true,
            'orders'   => $orders,        // можно $orders->toArray() если нужен чистый массив
            'statuses' => $statuses,
        ];
    }


    public function getPackerHistory()
    {
        try {
            /* 1. организация текущего пользователя */
            $orgId = Auth::user()->organization_id;

            /* 2. ВСЕ заказы этой организации – без фильтра по статусу */
            $orders = Order::with([
                            'orderProducts.productSubCard.productCard',
                            'packer:id,first_name,last_name,photo',
                            'courier:id,first_name,last_name,photo',
                            'client:id,first_name,last_name',
                            'statusDoc:id,name',
                        ])
                        ->where('organization_id', $orgId)
                        ->orderByDesc('created_at')
                        ->get();

            /* 3. справочник статусов id => name */
            $statuses = StatusDoc::pluck('name', 'id');   // {"uuid": "название", …}

            /* 4. обычный массив – Laravel сам сделает JSON */
            return [
                'success'  => true,
                'orders'   => $orders,
                'statuses' => $statuses,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }



public function getHistoryOrders()
{
    // Fetch orders where packer_document_id is not null
    $orders = Order::with(['orderProducts.productSubCard.productCard'])
        ->whereNotNull('packer_id')
        ->orderBy('created_at', 'desc')
        ->get();

    // Fetch statuses from the status_docs table (adjust table/column names as needed)
    $statuses = StatusDoc::select('id', 'name')->get();

    return response()->json([
        'success'  => true,
        'orders'   => $orders,
        'statuses' => $statuses  // return statuses array here
    ], 200);
}







public function getDetailedOrder($orderId)
{
    // $packerId = Auth::id();

    $order = Order::where('id', $orderId)
        // ->where('packer_id', $packerId)
        ->with([
            'orderProducts.productSubCard.productCard',
             // Include the source relationship
        ])
        ->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found or not authorized to view',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $order,
    ]);
}


// накладные фасовщика
public function updateOrderProducts(Request $request, $orderId)
{
    $validated = $request->validate([
        'products' => 'required|array',
        'products.*.id' => 'required|uuid|exists:products,id',
        'products.*.amount' => 'required|numeric|min:0',
    ]);

    $order = Order::findOrFail($orderId);

    foreach ($validated['products'] as $product) {
        $orderProduct = $order->products()->where('id', $product['id'])->first();
        if ($orderProduct) {
            $orderProduct->pivot->amount = $product['amount'];
            $orderProduct->pivot->save();
        }
    }

    return response()->json(['success' => true, 'message' => 'Order updated successfully.']);
}




public function confirmOrder(string $orderId)
{
    /* ── 0. Получаем заказ + позиции ─────────────────────────────── */
    $order = Order::with('orderItems')->findOrFail($orderId);

    if (!$order->packer_id) {
        return [
            'success' => false,
            'message' => 'У заказа отсутствует packer_id — подтверждение невозможно.',
        ];
    }

    /* ── 1. Находим UUID статуса «исполнено» ─────────────────────── */
    $doneStatusId = StatusDoc::where('name', 'исполнено')->value('id');
    if (!$doneStatusId) {
        return [
            'success' => false,
            'message' => 'Статус «исполнено» не найден в status_docs',
        ];
    }

    /* ── 2. UUID типа документа «sale» ───────────────────────────── */
    $saleTypeId = DocumentType::where('code', 'sale')->value('id');
    if (!$saleTypeId) {
        return [
            'success' => false,
            'message' => 'Тип документа с code = "sale" не найден в document_types',
        ];
    }

    /* ── 3. Транзакция: обновляем всё одним пакетом ──────────────── */
    DB::transaction(function () use ($order, $doneStatusId, $saleTypeId) {

        /* 3.1  Обновляем статус заказа */
        $order->update(['status_id' => $doneStatusId]);

        /* 3.2  Склад, привязанный к packer_id */
        $warehouse = Warehouse::where('packer_id', $order->packer_id)->firstOrFail();

        /* 3.3  Создаём документ-продажу */
        $document = Document::create([
            'document_type_id'  => $saleTypeId,
            'from_warehouse_id' => $warehouse->id,
            'client_id'         => $order->user_id,
            'worker_user_id'    => $order->packer_id,
            'document_date'     => now(),
            'status'            => 'confirmed',
            'comments'          => "Sale from Order #{$order->id}",
        ]);

        /* 3.4  Обрабатываем каждую позицию */
        foreach ($order->orderItems as $item) {

            // строка склада для того же товара и единицы измерения
            $whItem = WarehouseItem::where('warehouse_id',      $warehouse->id)
                       ->where('product_subcard_id', $item->product_subcard_id)
                       ->where('unit_measurement',   $item->unit_measurement)
                       ->first();

            // списываем остаток
            if ($whItem) {
                $whItem->quantity = max(0, $whItem->quantity - $item->quantity);
                $whItem->save();
            }

            // создаём строку документа
            DocumentItem::create([
                'document_id'        => $document->id,
                'product_subcard_id' => $item->product_subcard_id,
                'unit_measurement'   => $item->unit_measurement,
                'quantity'           => $item->quantity,
                'price'              => $item->price,
                'total_sum'          => $item->price * $item->quantity,

                // берём данные из warehouseItem (если есть) — иначе 0
                'brutto'             => $whItem->brutto              ?? 0,
                'netto'              => $whItem->netto               ?? 0,
                'cost_price'         => $whItem->cost_price          ?? 0,
                'additional_expenses'=> $whItem->additional_expenses ?? 0,
            ]);
        }
    });

    /* ── 4. Успешный ответ ───────────────────────────────────────── */
    return [
        'success' => true,
        'message' => 'Заказ подтверждён. Статус изменён на «исполнено», документ продажи создан.',
    ];
}
}
