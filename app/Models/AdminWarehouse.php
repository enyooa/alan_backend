<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminWarehouse extends Model
{
    use HasFactory;
    protected $fillable = [
        'organization_id',
        'product_card_id',
        'unit_measurement',
        'quantity',
        'price',
        'total_sum',
        'date',
           
    ];
}
