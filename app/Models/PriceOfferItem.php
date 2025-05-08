<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;  // ← import Str

class PriceOfferItem extends Model
{
    protected $table = 'price_offers_items';

    protected $fillable = [
        'price_offer_order_id',
        'product_subcard_id',
        'unit_measurement',   // имя единицы («Ящик»)
        'amount',             // количество
        'price',              // цена за единицу
    ];
    public $incrementing = false;
    protected $keyType   = 'string';
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }
    public function priceOfferOrder(): BelongsTo
    {
        return $this->belongsTo(PriceOfferOrder::class, 'price_offer_order_id');
    }

    public function productSubCard(): BelongsTo
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
    public function unit(): BelongsTo
{
    return $this->belongsTo(Unit_measurement::class, 'unit_measurement', 'name');
}
    public function product(): BelongsTo
{
    return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
}

}
