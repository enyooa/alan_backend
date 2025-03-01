<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(Request $request)
{
    // 1) If 'amount' is an empty string, convert to null
    if ($request->input('amount') === '') {
        $request->merge(['amount' => null]);
    }

    // 2) Validate with 'nullable|numeric' 
    //    so 'amount' can be null or a valid number
    $validated = $request->validate([
        'name'   => 'required|string',
        'amount' => 'nullable|numeric',
    ]);

    // 3) Create a new expense with the validated data
    $expense = Expense::create($validated);
    return response()->json($expense, 201);
}

    
    }
