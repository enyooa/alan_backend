<?php

namespace App\Http\Controllers;

use App\Models\AdminWarehouse;
use App\Models\PriceOffer;
use App\Models\Sale;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function fetchAllHistories()
{
    try {
        // 1) Get AdminWarehouse records
        $adminWarehouses = AdminWarehouse::all()->map(function($item){
            $arr = $item->toArray();
            // Tag this record with type = 'adminWarehouse'
            $arr['type'] = 'adminWarehouse';
            return $arr;
        });

        // 2) Get Sales records
        $sales = Sale::all()->map(function($sale){
            $arr = $sale->toArray();
            // Tag with type = 'sale'
            $arr['type'] = 'sale';
            return $arr;
        });

        $priceOffers = PriceOffer::all()->map(function($offer) {
            $arr = $offer->toArray();
            $arr['type'] = 'priceOffer';
            return $arr;
        });
        // 3) If you have more models, fetch them similarly,
        //    tagging each with a 'type'.

        // 4) Merge them into a single collection
        $all = $adminWarehouses->merge($sales)->merge($priceOffers);

        // Optionally sort them by date desc or something:
        // $all = $all->sortByDesc('date')->values();

        return response()->json($all, 200);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    
    public function update(Request $request, $type, $id)
    {
        try {
            switch ($type) {
                
                case 'adminWarehouse':
                    $model = AdminWarehouse::findOrFail($id);
                    // Validate the fields you want to allow updates for
                    $validatedData = $request->validate([
                        'organization_id'    => 'nullable|integer',
                        'product_subcard_id' => 'nullable|integer',
                        'unit_measurement'   => 'nullable|string|max:255',
                        'quantity'           => 'nullable|numeric|min:0',
                        'price'              => 'nullable|numeric|min:0',
                        'total_sum'          => 'nullable|numeric|min:0',
                        'date'               => 'nullable|date',
                        'brutto'             => 'nullable|numeric|min:0',
                        'netto'              => 'nullable|numeric|min:0',
                        'additional_expenses'=> 'nullable|numeric|min:0',
                        'cost_price'         => 'nullable|numeric|min:0',
                        'total_cost'         => 'nullable|numeric|min:0',
                    ]);
                    $model->update($validatedData);
                    return response()->json($model, 200);

                    case 'priceOffer':
                        $model = PriceOffer::findOrFail($id);
                        // Validate fields for price offers – adjust as needed.
                        $validatedData = $request->validate([
                            'client_id'  => 'required|integer',
                            'address_id' => 'required|integer',
                            'start_date' => 'required|date',
                            'end_date'   => 'required|date',
                            'totalsum'   => 'nullable|numeric|min:0',
                            // You might choose to save the price_offers array as JSON, for example:
                            // 'price_offers' => 'required|array'
                        ]);
                        $model->update($validatedData);
                        return response()->json($model, 200);
                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($type, $id)
    {
        try {
            switch ($type) {
               
                case 'adminWarehouse':
                    $model = AdminWarehouse::findOrFail($id);
                    $model->delete();
                    return response()->json(['message' => 'Запись успешно удалена.'], 200);

                case 'sale':
                    $model = Sale::findOrFail($id);
                    $model->delete();
                    return response()->json(['message' => 'Запись успешно удалена.'], 200);
                case 'priceOffer':
                    $model = PriceOffer::findOrFail($id);
                    $model->delete();
                    return response()->json(['message' => 'Запись успешно удалена.'], 200);
    
                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}