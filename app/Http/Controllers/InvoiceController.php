<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    // app/Http/Controllers/InvoiceController.php
public function index(Request $request): JsonResponse
{
    $orgId = $request->user()->organization_id;

    $rows = Order::forOrg($orgId)                // <-- your scope
        ->with([
            'client:id,first_name,last_name',
            'packer:id,first_name,last_name',
            'courier:id,first_name,last_name',
            'statusDoc:id,name',
            'organization:id,name',
        ])
        ->orderByDesc('created_at')
        ->paginate(
            $this->perPage($request)             // page size (15 default)
        )
        ->appends($request->query());            // keeps ?cashbox=… etc. on next/prev links

    return response()->json($rows);
}

public function show(Request $request, Order $order): JsonResponse
{
    // 1) block access if the order isn’t in *my* org
    $this->authorizeForOrg($request, $order);

    // 2) eager-load details
    $order->load([
        'packer:id,first_name,last_name',
        'courier:id,first_name,last_name',
        'client:id,first_name,last_name',
        'statusDoc:id,name',
        'organization:id,name',
        'orderItems' => function ($q) {
            $q->select(
                'id', 'order_id', 'product_subcard_id',
                'quantity', 'price', 'totalsum', 'unit_measurement'
            )
            ->with('productSubCard:id,name');
        },
    ]);

    return response()->json($order);
}

/* ------------------------------------------------------------------ */
/* helper: make the “belongs to my organisation?” check reusable      */
protected function authorizeForOrg(Request $request, Order $order): void
{
    if ($order->organization_id !== $request->user()->organization_id) {
        abort(403, 'Допуска нет.');
    }
}


   
}
