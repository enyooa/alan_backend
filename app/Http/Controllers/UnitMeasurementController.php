<?php

namespace App\Http\Controllers;

use App\Models\Unit_measurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnitMeasurementController extends Controller
{
    /**
     * Retrieve all unit measurements (HEAD version).
     */
    public function index()
    {
        $units = Unit_measurement::select('id', 'name', 'tare')->get(); // ✅ Ensure ID is included
        return response()->json($units);
    }

    /**
     * Create a new Unit Measurement
     */
    public function store(Request $request)
    {
        Log::info($request->all());

        // Validate that either name or tare is provided
        $request->validate([
            'name' => 'nullable|string|max:255|unique:unit_measurements,name',
            'tare' => 'nullable|numeric',
        ], [
            'name.unique' => 'Единица измерения с таким наименованием уже существует.',
        ]);

        // Ensure at least one field is filled
        if (!$request->filled('name') && !$request->filled('tare')) {
            return response()->json(['error' => 'Please provide either name or tare.'], 400);
        }

        // Create the unit measurement
        $unit = Unit_measurement::create([
            'name' => $request->input('name'),
            'tare' => $request->input('tare') !== null
                ? (float) $request->input('tare')
                : null, // Cast tare to double
        ]);

        return response()->json([
            'message' => 'Единица измерения успешно добавлена!',
            'data' => $unit
        ], 201);
    }

    /**
     * Update a Unit Measurement
     */
    public function update(Request $request, $id)
    {
        Log::info($id);

        $unit = Unit_measurement::findOrFail($id);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'tare' => 'nullable|numeric',
        ]);

        $unit->update($request->only(['name', 'tare']));

        return response()->json([
            'message' => '✅ Единица измерения успешно обновлена!',
            'data' => $unit
        ], 200);
    }

    /**
     * Delete a Unit Measurement
     */
    public function destroy($id)
    {
        $unit = Unit_measurement::findOrFail($id);
        $unit->delete();

        return response()->json(['message' => '❌ Единица измерения удалена!'], 200);
    }
}
