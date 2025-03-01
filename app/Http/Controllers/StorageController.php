<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdminWarehouse;   // HEAD line kept
use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use App\Models\GeneralWarehouse;
use App\Models\ProductSubCard;
use App\Models\Unit_measurement;
use App\Models\User;
use DocumentsRequests;
use GeneralWarehouses;           // HEAD line kept
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // HEAD line kept
use Illuminate\Support\Facades\Log;

class StorageController extends Controller
{
    /**
     * Get all storage users with "storage" role.
     */
    public function getStorageUsers()
    {
        try {
            $storageUsers = User::whereHas('roles', function ($query) {
                $query->where('name', 'storager');
            })->get(['id', 'first_name', 'last_name', 'surname','address']);

            return response()->json($storageUsers, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching storage users:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch storage users'], 500);
        }
    }

    public function getSubCards()
{
    try {
        // Получаем ID текущего пользователя
        $authUserId = Auth::id();

        // Если вам нужны все subCards (но при этом остаток для конкретного user),
        // мы можем оставить ProductSubCard::all().
        $subCards = ProductSubCard::all()->map(function ($subCard) use ($authUserId) {
            // Фильтруем GeneralWarehouse по product_subcard_id и auth_user_id = текущий юзер
            $adminWarehouseEntries = GeneralWarehouse::where('auth_user_id', $authUserId)
                ->where('product_subcard_id', $subCard->id)
                ->get();

            // Суммируем количество
            $remainingQuantity = $adminWarehouseEntries->sum('quantity');

            // Возьмём единицу измерения из первой записи
            $unitMeasurement = $adminWarehouseEntries->first()->unit_measurement ?? null;

            // Себестоимость (cost_price) также из первой записи
            $costPrice = $adminWarehouseEntries->first()->cost_price ?? null;

            return array_merge($subCard->toArray(), [
                'remaining_quantity' => $remainingQuantity,
                'unit_measurement'   => $unitMeasurement,
                'cost_price'         => $costPrice,
            ]);
        });

        return response()->json($subCards, 200);
    } catch (\Exception $e) {
        Log::error('Error fetching subcards with quantity and cost price', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to fetch subcards with quantity and cost price.'], 500);
    }
}


    /**
     * Bulk store inventory data to general_warehouses.
     */
    public function bulkStoreInventory(Request $request)
    {
        $validated = $request->validate([
            'inventory_checks' => 'required|array',
            'inventory_checks.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
            'inventory_checks.*.actual_amount'      => 'required|integer|min:0',
            'inventory_checks.*.date'               => 'required|date',
            'inventory_checks.*.batch_id'           => 'nullable|integer|exists:admin_warehouses,id',
            'inventory_checks.*.unit_measurement'   => 'nullable|string|max:255',
        ]);

        try {
            foreach ($validated['inventory_checks'] as $check) {
                if (!empty($check['batch_id'])) {
                    // Find the admin warehouse record using the provided batch ID
                    $batch = AdminWarehouse::find($check['batch_id']);
                    if (!$batch) {
                        throw new \Exception("Партия не найдена.");
                    }
                    // Update the admin_warehouse record
                    $batch->quantity = $check['actual_amount'];
                    $batch->save();
                }
            }

            return response()->json(['message' => 'Инвентаризация успешно сохранена!'], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении инвентаризации: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка при сохранении инвентаризации.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkWriteOff(Request $request)
    {
        $validated = $request->validate([
            'writeoffs' => 'required|array',
            'writeoffs.*.product_subcard_id' => 'required|integer|exists:product_sub_cards,id',
            'writeoffs.*.amount'             => 'required|integer|min:1',
            'writeoffs.*.batch_id'           => 'nullable|integer|exists:admin_warehouses,id',
            'writeoffs.*.unit_measurement'   => 'nullable|string|max:255',
        ]);

        try {
            foreach ($validated['writeoffs'] as $off) {
                if (!empty($off['batch_id'])) {
                    $batch = AdminWarehouse::find($off['batch_id']);
                    if (!$batch) {
                        throw new \Exception("Партия не найдена.");
                    }
                    if ($off['amount'] > $batch->quantity) {
                        throw new \Exception("Списание не может превышать доступное количество ({$batch->quantity}).");
                    }
                    $batch->quantity -= $off['amount'];
                    $batch->save();
                } else {
                    throw new \Exception("Партия не выбрана для товара с ID {$off['product_subcard_id']}.");
                }
            }

            return response()->json(['message' => 'Списание успешно выполнено!'], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при списании: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка при списании.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function sendToGeneralWarehouse(Request $request)
{
    Log::info($request->all());
    try {
        // Шаг 1: Валидируем
        $validated = $request->validate([
            'storager_id' => 'required|integer|exists:users,id',
            'address_id'  => 'required|integer|exists:addresses,id',
            'date'        => 'required|date',
            'inventory'   => 'required|array',
            'inventory.*.batch_id' => 'required|integer|exists:admin_warehouses,id',
            'inventory.*.amount'   => 'required|numeric|min:1',
        ]);

        // Шаг 2: Обработка каждой партии
        foreach ($validated['inventory'] as $item) {
            $adminRecord = AdminWarehouse::find($item['batch_id']);
            if (!$adminRecord) {
                throw new \Exception("Партия с ID {$item['batch_id']} не найдена.");
            }

            $transferAmount = $item['amount'];

            if ($transferAmount > $adminRecord->quantity) {
                throw new \Exception(
                    "Нельзя переместить больше, чем доступно (ID {$item['batch_id']}). ".
                    "Доступно: {$adminRecord->quantity}."
                );
            }

            // =========================
            // ВАЖНО: Расчёт ratio
            // =========================
            // Допустим, у нас в AdminWarehouse хранится
            // - brutto, netto, additional_expenses, cost_price, total_cost
            // на всё количество $adminRecord->quantity.
            // Если мы переносим только часть (transferAmount),
            // мы хотим учесть пропорцию:
            $originalQuantity = $adminRecord->quantity; // изначально
            $ratio = $transferAmount / $originalQuantity;

            // Пропорциональные поля
            $transferBrutto = ($adminRecord->brutto ?? 0) * $ratio;
            $transferNetto  = ($adminRecord->netto ?? 0) * $ratio;
            $transferExpenses = ($adminRecord->additional_expenses ?? 0) * $ratio;
            $transferCostPrice = $adminRecord->cost_price; 
            // cost_price (за еденицу) обычно то же самое,
            // но total_cost = (общая себестоимость) * ratio:
            $transferTotalCost = ($adminRecord->total_cost ?? 0) * $ratio;

            // Точно так же price можно считать за штуку,
            // а total_sum = price * transferAmount
            $transferPrice = $adminRecord->price;
            $transferTotalSum = $transferPrice * $transferAmount;

            // =========================
            // 1) Уменьшаем запись в AdminWarehouse
            // =========================
            $adminRecord->quantity -= $transferAmount;
            $adminRecord->brutto = max(($adminRecord->brutto ?? 0) - $transferBrutto, 0);
            $adminRecord->netto  = max(($adminRecord->netto  ?? 0) - $transferNetto, 0);
            $adminRecord->additional_expenses = max(($adminRecord->additional_expenses ?? 0) - $transferExpenses, 0);
            $adminRecord->total_cost = max(($adminRecord->total_cost ?? 0) - $transferTotalCost, 0);

            // price и cost_price обычно не меняются (за единицу товара),
            // но если нужно, пересчитайте. 
            // $adminRecord->price        = ...
            // $adminRecord->cost_price   = ...

            $adminRecord->save();

            // =========================
            // 2) Создаём новую запись в GeneralWarehouse
            // =========================
            GeneralWarehouse::create([
                'organization_id'    => $adminRecord->organization_id,
                'product_subcard_id' => $adminRecord->product_subcard_id,
                'user_id'            => $validated['storager_id'],
                'address_id'         => $validated['address_id'],

                'unit_measurement'   => $adminRecord->unit_measurement,

                'quantity'           => $transferAmount,
                'price'              => $transferPrice,
                'total_sum'          => $transferTotalSum,
                'date'               => $validated['date'],

                // пропорциональные поля
                'brutto'             => $transferBrutto,
                'netto'              => $transferNetto,
                'additional_expenses'=> $transferExpenses,
                'cost_price'         => $transferCostPrice,
                'total_cost'         => $transferTotalCost,
            ]);
        }

        return response()->json([
            'message' => 'Данные успешно перемещены из AdminWarehouse в GeneralWarehouse!',
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error in sendToGeneralWarehouse: ' . $e->getMessage());
        return response()->json([
            'error'   => 'Failed to transfer inventory',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Get inventory for a specific storager.
     */
    public function getInventory(Request $request)
    {
        try {
            $storagerId = $request->query('storager_id');

            $query = GeneralWarehouse::query();
            if ($storagerId) {
                $query->where('storager_id', $storagerId);
            }

            $inventories = $query->get();

            return response()->json($inventories, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch inventory'], 500);
        }
    }

    /**
     * Delete an inventory item from general_warehouses.
     */
    public function deleteInventory($id)
    {
        try {
            $inventory = GeneralWarehouse::findOrFail($id);
            $inventory->delete();

            return response()->json([
                'message' => 'Inventory item deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting inventory:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete inventory'], 500);
        }
    }

    public function getAllInstances(Request $request)
    {
        $clientsAndAddresses = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->with('addresses')->get(['id', 'first_name', 'last_name', 'surname']);
    
        $productSubCards = ProductSubCard::all();
        $unitMeasurements = Unit_measurement::all(['id', 'name']);

        return response()->json([
            'clients'           => $clientsAndAddresses,
            'product_sub_cards' => $productSubCards,
            'unit_measurements' => $unitMeasurements,
        ]);
    }

    public function storeSales(Request $request)
{
    Log::info($request->all());

    $validated = $request->validate([
        'client_id' => 'required|integer',
        'products'  => 'required|array',
        'products.*.product_subcard_id'     => 'required|integer',
        'products.*.price'                  => 'nullable|integer',
        'products.*.unit_measurement_id'    => 'required|integer',
        'products.*.quantity'               => 'nullable|integer',
        'products.*.brutto'                 => 'nullable|integer',
        'products.*.netto'                  => 'nullable|integer',
    ]);

    try {
        $authUserId = Auth::id(); // Текущий пользователь
        foreach ($validated['products'] as $product) {
            DocumentRequest::create([
                'client_id'           => $validated['client_id'],
                'product_subcard_id'  => $product['product_subcard_id'],
                'price'               => $product['price'] ?? null,
                'unit_measurement_id' => $product['unit_measurement_id'],
                'amount'              => $product['quantity'] ?? null,
                'brutto'              => $product['brutto'] ?? null,
                'netto'               => $product['netto'] ?? null,
                // Добавляем auth_user_id:
                'auth_user_id'        => $authUserId,
            ]);
        }

        return response()->json(['message' => 'Data successfully saved'], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    public function fetchSalesReport()
{
    // Получаем текущего пользователя
    $authUserId = Auth::id();

    // 1. Фильтруем DocumentRequest так, чтобы брать только те записи,
    //    которые принадлежат текущему пользователю (если в DocumentRequest есть 'auth_user_id')
    $sales = DocumentRequest::with('productSubcard', 'unitMeasurement')
        ->where('auth_user_id', $authUserId)
        ->get()
        ->map(function ($sale) use ($authUserId) {
            // 2. Находим остаток в GeneralWarehouse для этого subcard,
            //    тоже привязанный к auth_user_id, если у нас там есть это поле
            $remainingQuantity = GeneralWarehouse::where('auth_user_id', $authUserId)
                ->where('product_subcard_id', $sale->product_subcard_id)
                ->sum('quantity');

            // Формируем массив для каждой строки
            return [
                'product'   => $sale->productSubcard->name ?? 'Unknown',
                'unit'      => $sale->unitMeasurement->name ?? 'Unknown',
                'quantity'  => $sale->amount ?? 0,
                'price'     => $sale->price ?? 0,
                'total'     => ($sale->amount ?? 0) * ($sale->price ?? 0),
                'remaining' => $remainingQuantity ?? 0,
            ];
        });

    return response()->json(['sales' => $sales]);
}

    public function storageReceivingBulkStore(Request $request)
    {
        try {
            Log::info('Received bulk storage receiving data:', $request->all());

            $validated = $request->validate([
                'receivings'                => 'required|array',
                'receivings.*.subcard_id'   => 'required|integer',
                'receivings.*.unit_name'    => 'required|string|max:255',
                'receivings.*.quantity'     => 'required|numeric|min:0',
                'receivings.*.price'        => 'required|numeric|min:0',
                'receivings.*.date'         => 'required|date_format:Y-m-d',
            ]);

            foreach ($validated['receivings'] as $receiving) {
                GeneralWarehouse::create([
                    'product_subcard_id' => $receiving['subcard_id'],
                    'unit_measurement'   => $receiving['unit_name'],
                    'quantity'           => $receiving['quantity'],
                    'price'              => $receiving['price'],
                    'total_sum'          => $receiving['quantity'] * $receiving['price'],
                    'date'               => $receiving['date'],
                ]);
            }

            return response()->json([
                'message' => 'Bulk storage receiving successfully stored!',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error'    => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving storage receiving:', ['error' => $e->getMessage()]);
            return response()->json([
                'error'   => 'Failed to store storage receiving data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generalWarehouses()
    {
        $user = Auth::user();
        try {
            $generalWarehouses = GeneralWarehouse::where('auth_user_id',$user->id)->with('productSubCard:id,name')->get();

            $data = $generalWarehouses->map(function ($warehouse) {
                return [
                    'id'                => $warehouse->id,
                    'product_subcard_id'=> $warehouse->product_subcard_id,
                    'product_name'      => $warehouse->productSubCard->name ?? 'Unknown Product',
                    'quantity'          => $warehouse->quantity ?? 0,
                    'unit_measurement'  => $warehouse->unit_measurement,
                    'price'            => $warehouse->price,
                    'total_sum'        => $warehouse->total_sum,
                    'date'             => $warehouse->date,
                ];
            });

            return response()->json($data, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching general warehouse data:', ['error' => $e->getMessage()]);
            return response()->json([
                'error'   => 'Failed to fetch general warehouse data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process write-offs for General Warehouse.
     */
    public function writeOff(Request $request)
    {
        try {
            $validated = $request->validate([
                'write_offs' => 'required|array',
                'write_offs.*.product_subcard_id' => 'required|exists:general_warehouses,product_subcard_id',
                'write_offs.*.quantity' => 'required|numeric|min:0',
            ]);

            foreach ($validated['write_offs'] as $writeOff) {
                $warehouse = GeneralWarehouse::where('product_subcard_id', $writeOff['product_subcard_id'])->first();

                if ($warehouse && $warehouse->quantity >= $writeOff['quantity']) {
                    $warehouse->quantity -= $writeOff['quantity'];
                    $warehouse->save();
                } else {
                    return response()->json(['error' => 'Insufficient stock for product.'], 422);
                }
            }

            return response()->json(['message' => 'Write-off processed successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Write-off error:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to process write-off.'], 500);
        }
    }
}
