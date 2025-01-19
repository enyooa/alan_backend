<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialElement;
use App\Models\FinancialOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductSubCard;

class FinancialElementController extends Controller
{
    public function index()
    {
        return FinancialElement::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:income,expense',
        ]);

        $element = FinancialElement::create($request->all());
        return response()->json($element, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:income,expense',
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
     * Store a newly created financial order.
     */
    public function storeFinancialOrder(Request $request)
    {
        // Log the incoming request data
        //Log::info('Incoming request data:', $request->all());
    
        // Validate the incoming request
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
