<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str


class ProductCard extends Model
{
    use HasFactory;

    protected $table = 'product_cards';
    public $incrementing = false;       // ← add
    protected $keyType   = 'string';

    protected static function booted()
    {
        static::creating(fn ($product_cards) => $product_cards->id ??= (string) Str::uuid());
    }

    protected $fillable = [
        'organization_id',
        'name_of_products',
        'description',
        'country',
        'type',

        'photo_product',
    ];
    protected $appends = ['photo_product_url'];

    public function subCards()
    {
        return $this->hasMany(ProductSubCard::class, 'product_card_id');
    }


    public function getPhotoProductUrlAttribute()
    {
        return $this->photo_product ? asset('storage/' . $this->photo_product) : null;
    }

    // public function priceRequests()
    // {
    //     return $this->hasMany(PriceRequest::class, 'product_card_id');
    // }
}
