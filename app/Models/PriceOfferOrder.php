<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceOfferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'address_id',
        'warehouse_id',

        'start_date',
        'end_date',
        'totalsum'
    ];

    /**
     * Relationship to PriceOffer (HEAD version).
     */
    public function priceOffers()
    {
        return $this->hasMany(PriceOfferItem::class, 'price_offer_order_id');
    }
    /**
     * Relationship to ProductSubCard
     */
    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class);
    }

    /**
     * Relationship to PriceRequest (via items).
     */
    public function items()          // ← контроллер использует именно items()
    {
        return $this->hasMany(PriceOfferItem::class, 'price_offer_order_id');
    }

    public function client()     { return $this->belongsTo(User::class,     'client_id');  }
    public function address()    { return $this->belongsTo(Address::class,  'address_id'); }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
