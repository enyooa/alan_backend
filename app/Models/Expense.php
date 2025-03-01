<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
    ];

    // Many-to-Many с AdminWarehouse
    public function adminWarehouses()
    {
        return $this->belongsToMany(
            AdminWarehouse::class,
            'admin_warehouse_expense',
            'expense_id',
            'admin_warehouse_id'
        )
        // ->withPivot('expense_amount') // если нужно поле
        ->withTimestamps();
    }
}
