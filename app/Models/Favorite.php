<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Favorite extends Model
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
        'organization_id',
        'user_id',
        'product_subcard_id',
        'source_table',
        'price',
        'unit_measurement',
        'totalsum',

    ];

    // Relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with the ProductSubCard model
    public function productSubcard()
    {
        return $this->belongsTo(ProductSubCard::class);
    }
}
