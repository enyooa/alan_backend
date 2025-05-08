<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Provider extends Model
{
    use HasFactory;
    protected $fillable = ['name','organization_id'];
    public $incrementing = false;
    protected $keyType   = 'string';
    // app/Models/FinancialOrder.php
public function provider()
{
    return $this->belongsTo(Provider::class, 'provider_id');
}

protected static function boot()
    {
        parent::boot();                       // ← не забываем!

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    public function financialOrders()
    {
        return $this->hasMany(FinancialOrder::class);
    }
}
