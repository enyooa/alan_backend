<?php

namespace App\Http\Controllers;

use App\Models\Unit_measurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class UnitMeasurementController extends Controller
{
    public function index()
{
    $units = Unit_measurement::select('name', 'tare')->distinct()->get();

    return response()->json($units);
}


    public function store(Request $request)
    {
        Log::info($request->all());
    
        // Validate that either name or tare is provided
        $request->validate([
            'name' => 'nullable|string|max:255',
            'tare' => 'nullable|numeric', // Ensure tare is a number (double)
        ]);
    
        // Ensure at least one field is filled
        if (!$request->filled('name') && !$request->filled('tare')) {
            return response()->json(['error' => 'Please provide either name or tare.'], 400);
        }
    
        // Create the unit measurement
        $unit = Unit_measurement::create([
            'name' => $request->input('name'),
            'tare' => $request->input('tare') !== null ? (float) $request->input('tare') : null, // Cast tare to double
        ]);
    
        return response()->json([
            'message' => 'Единица измерения успешно добавлена!',
            'data' => $unit
        ], 201);
    }
    


    public function update(Request $request, Unit_measurement $unit)
    {
        $unit->update($request->all());
        return response()->json($unit, 200);
    }

    public function destroy(Unit_measurement $unit)
    {
        $unit->delete();
        return response()->json(['message' => 'Unit deleted'], 200);
    }
}
