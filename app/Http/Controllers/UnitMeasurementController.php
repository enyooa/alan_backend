<?php

namespace App\Http\Controllers;

use App\Models\Unit_measurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class UnitMeasurementController extends Controller
{
    public function index()
    {
        return response()->json(Unit_measurement::all(), 200);
    }

    public function store(Request $request)
{
    $request->validate(['name' => 'required']);

    $unit = Unit_measurement::create($request->only('name'));
    return response()->json($unit, 201);
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
