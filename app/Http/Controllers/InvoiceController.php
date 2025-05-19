<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // опционально: показывать только накладные организации пользователя
        $orgId = $request->user()->organization_id ?? null;

        $rows = Order::with([
                    // минимальные поля, чтобы не тащить всё подряд
                    'client:id,first_name,last_name',
                    'packer:id,first_name,last_name',
                    'courier:id,first_name,last_name',
                    'statusDoc:id,name',
                    'organization:id,name',
                ])
                ->when($orgId, fn($q) => $q->where('organization_id', $orgId))
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'address',
                    'status_id',
                    'user_id',     // client
                    'packer_id',
                    'courier_id',
                    'place_quantity',
                    'organization_id',
                    'created_at',
                ]);

        return response()->json($rows);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'packer:id,first_name,last_name',
            'courier:id,first_name,last_name',
            'client:id,first_name,last_name',
            'statusDoc:id,name',
            'organization:id,name',
            'orderItems' => function ($q) {
                $q->select('id','order_id','product_subcard_id',
                           'quantity','price','totalsum',
                           'unit_measurement'
                           )
                  ->with('productSubCard:id,name');
            },
        ]);

        return response()->json($order);
    }
}
