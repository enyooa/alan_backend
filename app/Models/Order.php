<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
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
        'user_id',
        'status_id',
        'address',
        'packer_id',
        'courier_id',
        'organization_id'

    ];
    public function packer()
{
    return $this->belongsTo(User::class, 'packer_id');
}
public function courier()
{
    return $this->belongsTo(User::class, 'courier_id');
}

public function orderItems()
{
    return $this->hasMany(OrderItem::class, 'order_id');
}
public function orderProducts()
{
    return $this->hasMany(OrderItem::class, 'order_id');
}
public function client()
{
    return $this->belongsTo(User::class, 'user_id');
}
public function statusDoc()
{
    return $this->belongsTo(StatusDoc::class, 'status_id');
}

}
