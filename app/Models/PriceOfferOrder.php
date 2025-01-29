<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceOfferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_request_id',
        'product_subcard_id',
        'unit_measurement',
        'amount',
        'price',
        'total',
    ];

    public function priceRequest()
    {
        return $this->belongsTo(PriceRequest::class, 'price_request_id');
    }

    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class);
    }
    public function items()
    {
        return $this->hasMany(PriceRequest::class, 'price_request_id');
    }
}

