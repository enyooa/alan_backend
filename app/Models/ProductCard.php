<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCard extends Model
{
    use HasFactory;

    protected $table = 'product_cards';

    protected $fillable = [
        'name_of_products',
        'description',
        'country',
        'type',
        
        'photo_product',
    ];

    public function subCards()
    {
        return $this->hasMany(ProductSubCard::class, 'product_card_id');
    }
    

    public function priceRequests()
    {
        return $this->hasMany(PriceRequest::class, 'product_card_id');
    }
}
