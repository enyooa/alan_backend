<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class ProductSubCard extends Model
{
    use HasFactory;
    public $incrementing = false;       // ← add
    protected $keyType   = 'string';
    protected $table = 'product_sub_cards';

    protected $fillable = [
        'product_card_id',
        'name',
'organization_id'
    ];
    protected static function booted()
    {
        static::creating(fn ($productsubcard) => $productsubcard->id ??= (string) Str::uuid());
    }
    public function productCard()
{
    return $this->belongsTo(ProductCard::class, 'product_card_id');
}

}
