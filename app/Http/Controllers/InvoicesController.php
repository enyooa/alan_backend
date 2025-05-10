<?php
// app/Http/Controllers/Api/InvoiceController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    /**
     * Список накладных текущей организации (или все — при отсутствии org-фильтра).
     * В ответе: клиент, упаковщик, курьер, статус, организация.
     */
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
}
