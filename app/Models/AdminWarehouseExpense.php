<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminWarehouseExpense extends Model
{
    protected $table = 'admin_warehouse_expense';

    protected $fillable = [
        'admin_warehouse_id',
        'expense_id',
    ];

    
}
