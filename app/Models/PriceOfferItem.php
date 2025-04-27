<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceOfferItem extends Model
{
    protected $table = 'price_offer_items';

    protected $fillable = [
        'price_offer_order_id',
        'product_subcard_id',
        'unit_measurement',   // имя единицы («Ящик»)
        'amount',             // количество
        'price',              // цена за единицу
    ];
    public function priceOfferOrder(): BelongsTo
    {
        return $this->belongsTo(PriceOfferOrder::class, 'price_offer_order_id');
    }

    public function productSubCard(): BelongsTo
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
    public function unitRef(): BelongsTo
    {
        return $this->belongsTo(ReferenceItem::class,
                                'unit_measurement', 'name');
        // <─ связываемся по NAME
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(ReferenceItem::class,
                                'product_subcard_id');
    }
}
