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
    $user = Auth::user();


    // Get all the role IDs for this user
    $roleIds = $user->roles->pluck('id');

    // Return only the elements whose role_id matches any of the user's roles
    return FinancialElement::whereIn('role_id', $roleIds)->get();
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


    public function update(Request $request, $id)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|string|in:income,expense',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $element = FinancialElement::findOrFail($id);
        $element->update($request->all());

        return response()->json($element, 200);
    }

    public function destroy($id)
    {
        $element = FinancialElement::findOrFail($id);
        $element->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }


    // создать приходной ордер
    public function financialOrder()
    {
        $financialOrders = FinancialOrder::with(['adminCash', 'user', 'financialElement', 'productSubcard'])->get();
        return response()->json($financialOrders, 200);
    }

    /**
     * для клиента создаем
     */
    public function storeFinancialOrder(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'admin_cash_id' => 'required|exists:admin_cashes,id',
            'user_id' => 'required|exists:users,id',
            'financial_element_id' => 'required|exists:financial_elements,id',
            'product_subcard_id' => 'nullable|exists:product_sub_cards,id',
            'summary_cash' => 'required|integer',
            'date_of_check' => 'required|date',
            'photo_of_check' => 'nullable|file|mimes:jpeg,png,jpg',
        ]);

        if ($validator->fails()) {
            // Log validation errors
            //Log::info('Validation failed:', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $photoPath = null;

        // Handle photo upload
        if ($request->hasFile('photo_of_check')) {
            //Log::info('photo_of_check exists in the request.');
            $photoFile = $request->file('photo_of_check');

            if ($photoFile->isValid()) {
                // Log the original name of the uploaded file
                //Log::info('Uploaded photo name: ' . $photoFile->getClientOriginalName());

                // Create a unique filename and save the file
                $filename = uniqid() . '_' . $photoFile->getClientOriginalName();
                $photoPath = $photoFile->storeAs('financial_orders', $filename, 'public');

                // Log the storage path
                //Log::info('Photo stored at: ' . $photoPath);
            } else {
                Log::error('Photo upload is invalid.');
                return response()->json(['message' => 'Invalid photo upload.'], 400);
            }
        } else {
            //Log::info('No photo uploaded.');
        }

        // Create the financial order
        $financialOrder = FinancialOrder::create([
            'type' => $request->type,
            'admin_cash_id' => $request->admin_cash_id,
            'user_id' => $request->user_id,
            'financial_element_id' => $request->financial_element_id,
            'product_subcard_id' => $request->product_subcard_id,
            'summary_cash' => $request->summary_cash,
            'date_of_check' => $request->date_of_check,
            'photo_of_check' => $photoPath ? $photoPath : null,
        ]);

        // If photo was uploaded, generate a public URL for it
        if ($photoPath) {
            $financialOrder->photo_of_check = asset('storage/' . $photoPath);
        }

        //Log::info('Financial order created successfully:', $financialOrder->toArray());

        return response()->json($financialOrder, 201);
    }

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
            return response()->json(['message' => 'Financial Order not found'], 404);
        }

        // Delete associated photo if exists
        if ($financialOrder->photo_of_check) {
            Storage::disk('public')->delete($financialOrder->photo_of_check);
        }

        $financialOrder->delete();

        return response()->json(['message' => 'Financial Order deleted successfully'], 200);
    }
}
