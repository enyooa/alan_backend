<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderStatusLog extends Model
{


    protected $fillable = ['order_id', 'status', 'remarks', 'changed_at','organization_id'];
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
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
