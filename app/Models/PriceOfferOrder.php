<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceOfferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'address_id', 'start_date', 'end_date', 'totalsum'

    ];

    public function priceOffers(): HasMany
    {
        return $this->hasMany(PriceOffer::class);
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

