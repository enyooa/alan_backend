<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        /* --------------------------------------------------------
           1)  «Подчищаем» ввод: если amount = "" → null
        -------------------------------------------------------- */
        if ($request->input('amount') === '') {
            $request->merge(['amount' => null]);
        }

        /* --------------------------------------------------------
           2)  Валидация
               — name обязательно
               — amount может быть null или числом
               — (опция) name уникален в рамках организации
        -------------------------------------------------------- */
        $validated = $request->validate([
            'name'   => [
                'required',
                'string',
                // если нужно уникальное название внутри своей организации 👇
                // Rule::unique('expenses')
                //     ->where('organization_id', $request->user()->organization_id),
            ],
            'amount' => 'nullable|numeric',
        ]);

        /* --------------------------------------------------------
           3)  Добавляем organization_id из авторизованного пользователя
        -------------------------------------------------------- */
        $validated['organization_id'] = $request->user()->organization_id;

        /* --------------------------------------------------------
           4)  Создаём запись
        -------------------------------------------------------- */
        $expense = Expense::create($validated);

        return response()->json($expense, 201);
    }


    }
