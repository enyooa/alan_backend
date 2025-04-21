<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialElement;
use App\Models\FinancialOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductSubCard;
use Illuminate\Support\Facades\Auth;

class FinancialElementController extends Controller
{
    public function index()
    {
        $roleIds = Auth::user()->roles->pluck('id');
        return FinancialElement::whereIn('role_id', $roleIds)->get();
    }

    /** GET /financial-elements/{type} – income OR expense, still role‑filtered */
    public function byType(string $type)
    {
        // $type is guaranteed by the route regex to be “income” or “expense”
        $roleIds = Auth::user()->roles->pluck('id');

        return FinancialElement::where('type', $type)
               ->whereIn('role_id', $roleIds)
               ->get();
    }


    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|string|in:income,expense',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        // Create a new FinancialElement including the role_id
        // Make sure 'role_id' is in $fillable on the FinancialElement model
        $element = FinancialElement::create($request->all());

        return response()->json($element, 201);
    }


    /**
 * PATCH /api/financial-orders/{id}
 */
public function updateFinancialOrder(Request $request, int $id)
{
    Log::info($request->all());

    /* 1.  Валидация (полустрогая — поля «иногда|required»)  */
    $data = $request->validate([
        'type'                  => 'sometimes|required|string|in:income,expense',
        'admin_cash_id'         => 'sometimes|required|exists:admin_cashes,id',
        'financial_element_id'  => 'sometimes|required|exists:financial_elements,id',
        'summary_cash'          => 'sometimes|required|integer',
        'date_of_check'         => 'sometimes|nullable|date',

        'counterparty_id'       => 'sometimes|required|integer',
        'counterparty_type'     => 'sometimes|required|string|in:client,provider',

        // файл – опционально
        'photo_of_check'        => 'sometimes|file|image|mimes:jpeg,png|max:2048',
    ]);

    /* 2.  Находим ордер  */
    $order = FinancialOrder::findOrFail($id);

    /* 3.  Обновляем FK‑поля, если пришли -------------------- */
    if (array_key_exists('counterparty_id', $data)
        && array_key_exists('counterparty_type', $data)) {

        $order->user_id     = null;
        $order->provider_id = null;

        if ($data['counterparty_type'] === 'provider') {
            $order->provider_id = $data['counterparty_id'];
        } else {                                  // client
            $order->user_id = $data['counterparty_id'];
        }
    }

    /* 4.  Массовое присвоение остальных колонок  */
    $order->fill($data);

    /* 5.  Обновляем фото, если есть  */
    if ($request->hasFile('photo_of_check')) {
        $path = $request->file('photo_of_check')
                        ->store('financial_orders', 'public');
        $order->photo_of_check = $path;
    }

    $order->save();

    return response()->json($order->fresh([
        'adminCash:id,name',
        'financialElement:id,name',
        'user:id,first_name,last_name',
        'provider:id,name',
    ]), 200);
}

    public function destroy($id)
    {
        $element = FinancialElement::findOrFail($id);
        $element->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }


    // создать приходной ордер
    public function financialOrder(Request $request)
    {
        $orders = FinancialOrder::with([
                'adminCash',          // название кассы
                'user',
                'financialElement',   // статья
                'productSubcard'
            ])

            /* ---------- диапазон дат ---------- */
            ->when(
                $request->filled('date_from') && $request->filled('date_to'),
                fn ($q) => $q->whereBetween('date_of_check', [
                    $request->date_from,
                    $request->date_to
                ])
            )

            /* ---------- фильтр по кассе ---------- */
            ->when(
                $request->filled('cashbox'),
                fn ($q) => $q->whereHas('adminCash', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->cashbox . '%');
                })
            )

            /* ---------- фильтр по статье (НОВОЕ) ---------- */
            ->when(
                $request->filled('element'),
                fn ($q) => $q->whereHas('financialElement', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->element . '%');
                })
            )

            ->orderByDesc('date_of_check')
            ->get();

        return response()->json($orders, 200);
    }


    /** GET /financial-orders/{type} — income  или  expense */
    public function financialOrderByType(Request $request, string $type)
    {
        /* ------------ выборка с жадной загрузкой связей ------------- */
        $orders = FinancialOrder::query()
            ->where('type', $type)

            // eager‑load: нужные поля, чтобы не тащить всё подряд
            ->with([
                'adminCash:id,name,IBAN',
                'financialElement:id,name,type',
                'user:id,first_name,last_name,surname,whatsapp_number',
                'provider:id,name',
                'productSubcard:id,name',
            ])

            /* ----------- фильтр: диапазон дат ------------------------ */
            ->when(
                $request->filled(['date_from', 'date_to']),
                fn ($q) => $q->whereBetween('date_of_check', [
                    $request->date_from,
                    $request->date_to,
                ])
            )

            /* ----------- фильтр: название кассы ---------------------- */
            ->when(
                $request->filled('cashbox'),
                fn ($q) => $q->whereHas('adminCash', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->cashbox . '%');
                })
            )

            /* ----------- фильтр: статья движения --------------------- */
            ->when(
                $request->filled('element'),
                fn ($q) => $q->whereHas('financialElement', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->element . '%');
                })
            )

            ->orderByDesc('date_of_check')
            ->get()

            // убираем лишние FK‑поля из ответа
            ->makeHidden([
                'user_id',
                'provider_id',
                'admin_cash_id',
                'financial_element_id',
                'product_subcard_id',
            ]);

        return response()->json($orders, 200);
    }

    /**
     * для клиента создаем
     */
    // public function storeFinancialOrder(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'type' => 'required|string',
    //         'admin_cash_id' => 'required|exists:admin_cashes,id',
    //         'user_id' => 'required|exists:users,id',
    //         'financial_element_id' => 'required|exists:financial_elements,id',
    //         'product_subcard_id' => 'nullable|exists:product_sub_cards,id',
    //         'summary_cash' => 'required|integer',
    //         'date_of_check' => 'required|date',
    //         'photo_of_check' => 'nullable|file|mimes:jpeg,png,jpg',
    //     ]);

    //     if ($validator->fails()) {
    //         // Log validation errors
    //         //Log::info('Validation failed:', $validator->errors()->toArray());
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $photoPath = null;

    //     // Handle photo upload
    //     if ($request->hasFile('photo_of_check')) {
    //         //Log::info('photo_of_check exists in the request.');
    //         $photoFile = $request->file('photo_of_check');

    //         if ($photoFile->isValid()) {
    //             // Log the original name of the uploaded file
    //             //Log::info('Uploaded photo name: ' . $photoFile->getClientOriginalName());

    //             // Create a unique filename and save the file
    //             $filename = uniqid() . '_' . $photoFile->getClientOriginalName();
    //             $photoPath = $photoFile->storeAs('financial_orders', $filename, 'public');

    //             // Log the storage path
    //             //Log::info('Photo stored at: ' . $photoPath);
    //         } else {
    //             Log::error('Photo upload is invalid.');
    //             return response()->json(['message' => 'Invalid photo upload.'], 400);
    //         }
    //     } else {
    //         //Log::info('No photo uploaded.');
    //     }

    //     // Create the financial order
    //     $financialOrder = FinancialOrder::create([
    //         'type' => $request->type,
    //         'admin_cash_id' => $request->admin_cash_id,
    //         'user_id' => $request->user_id,
    //         'financial_element_id' => $request->financial_element_id,
    //         'product_subcard_id' => $request->product_subcard_id,
    //         'summary_cash' => $request->summary_cash,
    //         'date_of_check' => $request->date_of_check,
    //         'photo_of_check' => $photoPath ? $photoPath : null,
    //     ]);

    //     // If photo was uploaded, generate a public URL for it
    //     if ($photoPath) {
    //         $financialOrder->photo_of_check = asset('storage/' . $photoPath);
    //     }

    //     //Log::info('Financial order created successfully:', $financialOrder->toArray());

    //     return response()->json($financialOrder, 201);
    // }

    // для кассы
    public function storeFinancialOrders(Request $request)
    {
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'type'               => 'required|string|in:expense,income', // etc.
            'admin_cash_id'      => 'required|exists:admin_cashes,id',
            'financial_element_id'=> 'required|exists:financial_elements,id',
            'summary_cash'       => 'required|integer',
            'date_of_check'      => 'required|date',

            // 2) The combined field:
            'counterparty_id'    => 'required|integer',
            'counterparty_type'  => 'required|string|in:client,provider',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3) Decide whether to store in user_id or provider_id
        $userId = null;
        $providerId = null;

        if ($request->counterparty_type === 'provider') {
            // if the providers table has an ID to match
            // e.g. check providers table if needed
            $providerId = $request->counterparty_id;
        } else {
            // assume 'client'
            $userId = $request->counterparty_id;
        }

        // 4) Create the financial order
        $financialOrder = new FinancialOrder();
        $financialOrder->type = $request->type;
        $financialOrder->admin_cash_id = $request->admin_cash_id;
        $financialOrder->financial_element_id = $request->financial_element_id;
        $financialOrder->summary_cash = $request->summary_cash;
        $financialOrder->date_of_check = $request->date_of_check;

        // store user_id or provider_id
        $financialOrder->user_id = $userId;
        $financialOrder->provider_id = $providerId;

        // handle optional fields (photo, product_subcard_id, etc.)
        // $financialOrder->photo_of_check = ...
        // $financialOrder->product_subcard_id = $request->product_subcard_id;
        // ...

        $financialOrder->save();

        return response()->json($financialOrder, 201);
    }



    /**
     * Show the specified financial order.
     */
    public function showFinancialOrder($id)
    {
        $financialOrder = FinancialOrder::with(['adminCash', 'user', 'financialElement', 'productSubcard'])->find($id);

        if (!$financialOrder) {
            return response()->json(['message' => 'Financial Order not found'], 404);
        }

        return response()->json($financialOrder, 200);
    }

    /**
     * Delete the specified financial order.
     */
    public function destroyFinancialOrder($id)
    {
        $financialOrder = FinancialOrder::find($id);

        if (!$financialOrder) {
            return response()->json(['message' => 'Финансовый ордер не найден'], 404);
        }

        // Delete associated photo if exists
        if ($financialOrder->photo_of_check) {
            Storage::disk('public')->delete($financialOrder->photo_of_check);
        }

        $financialOrder->delete();

        return response()->json(['message' => 'Финансовый ордер успешно удален!'], 200);
    }
}
