<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Basket extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_client_request',
        'product_subcard_id',
        'source_table',
        'source_table_id',
        'quantity',
        'price',
        'unit_measurement',  // <-- now fillable
        'totalsum',
    ];

    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
}
