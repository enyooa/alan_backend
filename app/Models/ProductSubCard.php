<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSubCard extends Model
{
    use HasFactory;

    protected $table = 'product_sub_cards';

    protected $fillable = [
        'product_card_id',
        'name',
        'brutto',
        'netto'
    ];
    
    public function productCard()
{
    return $this->belongsTo(ProductCard::class, 'product_card_id');
}

}
