<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', // Add this line
        'product_subcard_id',
        'source_table',
        'quantity',
        'price',
    ];
    public function productSubCard()
{
    return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
}
public function order()
{
    return $this->belongsTo(Order::class, 'order_id'); // Links OrderItem back to its Order
}

}
