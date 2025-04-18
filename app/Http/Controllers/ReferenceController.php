<?php

namespace App\Http\Controllers;

use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Unit_measurement;
use App\Models\Address;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferenceController extends Controller
{
    // 1) Fetch data by type
    public function fetch($type)
    {
        try {
            switch ($type) {
                case 'productCard':
                    $products = ProductCard::all()->map(function ($product) {
                        $product->photo_url = $product->photo_product
                            ? url('storage/' . $product->photo_product)
                            : null;
                        return $product;
                    });
                    return response()->json($products, 200);

                case 'subproductCard':
                    $subCards = ProductSubCard::all();
                    return response()->json($subCards, 200);

                case 'provider':
                    $providers = Provider::all();
                    return response()->json($providers, 200);

                case 'unit':
                    $units = Unit_measurement::all();
                    return response()->json($units, 200);

                case 'address':
                    $addresses = Address::all();
                    return response()->json($addresses, 200);

                case 'expense':  // ADD THIS
                    $expenses = Expense::all();
                    return response()->json($expenses, 200);

                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchOne($type, $id)
{
    Log::info($type);
    Log::info($id);
    try {
        switch ($type) {
            case 'productCard':
                $model = ProductCard::findOrFail($id);
                // Optionally add 'photo_url' if you store an image
                $model->photo_url = $model->photo_product
                    ? url('storage/' . $model->photo_product)
                    : null;
                return response()->json($model, 200);

            case 'subproductCard':
                $model = ProductSubCard::findOrFail($id);
                return response()->json($model, 200);

            case 'provider':
                $model = Provider::findOrFail($id);
                return response()->json($model, 200);
            case 'unit':
                $model = Unit_measurement::findOrFail($id);
                return response()->json($model, 200);
            case 'expense':
                $model = Expense::findOrFail($id);
                return response()->json($model, 200);
            // etc. for unit, address, expense
            default:
                return response()->json(['error' => 'Invalid reference type.'], 400);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // 2) Unified update method (PATCH)
    public function update(Request $request, $type, $id)
{
    try {
        switch ($type) {
            case 'productCard':
                $model = ProductCard::findOrFail($id);
                $validatedData = $request->validate([
                    'name_of_products' => 'required|string',
                    'description'      => 'nullable|string',
                    'country'          => 'nullable|string',
                    'type'             => 'nullable|string',
                    'photo_product'    => 'nullable'
                ]);

                if ($request->hasFile('photo_product')) {
                    $path = $request->file('photo_product')->store('products', 'public');
                    $validatedData['photo_product'] = $path;
                }

                $model->update($validatedData);
                return response()->json($model, 200);

            case 'subproductCard':
                $model = ProductSubCard::findOrFail($id);
                $validatedData = $request->validate([
                    'product_card_id' => 'required|integer',
                    'name'            => 'required|string',
                ]);
                break;

            case 'provider':
                $model = Provider::findOrFail($id);
                $validatedData = $request->validate([
                    'name' => 'required|string',
                ]);
                break;

            case 'unit':
                // HERE is the ONLY change: we added unique validation.
                $model = Unit_measurement::findOrFail($id);

                $validatedData = $request->validate([
                    'name' => [
                        'required',
                        'string',
                        "unique:unit_measurements,name,{$id},id"
                    ],
                    'tare' => 'nullable|numeric',
                ], [
                    'name.unique' => 'Единица измерения с таким наименованием уже существует.',
                ]);
                break;

            case 'address':
                $model = Address::findOrFail($id);
                $validatedData = $request->validate([
                    'name' => 'required|string',
                ]);
                break;

            case 'expense':
                $model = Expense::findOrFail($id);
                $validatedData = $request->validate([
                    'name'   => 'required|string',
                    'amount' => 'nullable|numeric',
                ]);
                break;

            default:
                return response()->json(['error' => 'Invalid reference type.'], 400);
        }

        // Update the found model with validated data
        $model->update($validatedData);
        return response()->json($model, 200);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // 3) Destroy method (DELETE)
    public function destroy($type, $id)
    {

        try {
            switch ($type) {
                case 'productCard':
                    $model = ProductCard::findOrFail($id);
                    break;

                case 'subproductCard':
                    $model = ProductSubCard::findOrFail($id);
                    break;

                case 'provider':
                    $model = Provider::findOrFail($id);
                    break;

                case 'unit':
                    $model = Unit_measurement::findOrFail($id);
                    break;

                case 'address':
                    $model = Address::findOrFail($id);
                    break;

                case 'expense':  // ADD THIS
                    $model = Expense::findOrFail($id);
                    break;

                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }

            $model->delete();
            return response()->json(['message' => 'Запись успешно удалена.'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
