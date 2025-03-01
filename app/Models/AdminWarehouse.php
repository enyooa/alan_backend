<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminWarehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'product_subcard_id',
        'unit_measurement',
        'quantity',
        'price',
        'total_sum',
        'date',
        'brutto',
        'netto',
        'additional_expenses',
        'cost_price',
        'total_cost',
    ];

    // Many-to-Many с Expense
    public function expenses()
    {
        return $this->belongsToMany(
            Expense::class,                // Модель "Expense"
            'admin_warehouse_expense',     // пивот-таблица
            'admin_warehouse_id',          // pivot FK для этой модели
            'expense_id'                   // pivot FK для модели Expense
        )
        // ->withPivot('expense_amount') // если нужно поле expense_amount
        ->withTimestamps();
    }
}
