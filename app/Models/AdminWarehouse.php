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
    ];
}
