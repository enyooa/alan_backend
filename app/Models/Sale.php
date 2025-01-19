<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_subcard_id',
        'unit_measurement',
        'amount',
        'price',
        'totalsum',
    ];

    public function subCard()
{
    return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
}
}
