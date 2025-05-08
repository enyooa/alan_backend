<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Basket extends Model
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
        'id_client_request',
        'product_subcard_id',
        'source_table',
        'source_table_id',
        'quantity',
        'price',
        'unit_measurement',  // <-- now fillable
        'totalsum',
        'organization_id'
    ];

    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
}
