<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceOfferItem extends Model
{
    protected $table = 'price_offer_items'; // If you want to be explicit

    protected $fillable = [
        'choice_status',
        'price_offer_order_id',
        'product_subcard_id',
        'unit_measurement',
        'amount',
        'price',
        'totalsum',
        'start_date',
        'end_date',
    ];

    public function priceOfferOrder(): BelongsTo
    {
        return $this->belongsTo(PriceOfferOrder::class, 'price_offer_order_id');
    }

    public function productSubCard(): BelongsTo
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
}
