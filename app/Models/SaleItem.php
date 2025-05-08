<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id','product_subcard_id','unit_measurement',
        'amount','price','total_sum'
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
    public function sale()   { return $this->belongsTo(Sale::class); }
    public function product(){ return $this->belongsTo(ProductSubCard::class,'product_subcard_id'); }
}
