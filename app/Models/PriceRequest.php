<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BasicProductsPrice;

class PriceRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'choice_status',
        'user_id',
        'address_id',
        'product_subcard_id',
        'unit_measurement',
        'amount',
        'price',
        'start_date',
        'end_date',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Product
    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
    public function orders()
    {
        return $this->hasMany(PriceOfferOrder::class);
    }
    public function order()
    {
        return $this->belongsTo(PriceOfferOrder::class, 'price_request_id');
    }
    public function priceOfferOrders()
    {
        return $this->hasMany(PriceOfferOrder::class, 'price_request_id');
    }
}
