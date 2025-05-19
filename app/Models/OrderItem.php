<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;
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
    protected $fillable = [
        'order_id',
        'packer_quantity',
        'courier_quantity',
        'product_subcard_id',
        'source_table',
        'source_table_id',
        'quantity',
        'unit_measurement',
        'totalsum',
        'price',
    ];
    public function productSubCard()
{
    return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
}
public function order()
{
    return $this->belongsTo(Order::class, 'order_id');
}
public function source()
{
    return $this->morphTo(null, 'source_table', 'source_table_id');
}

// app/Models/OrderItem.php
public function unit()
{
    return $this->belongsTo(Unit_measurement::class, 'unit_measurement'); // ← имя поля в таблице
}

}
