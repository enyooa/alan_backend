<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'status_id',
        'address',
        'packer_id',
        'courier_id',

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
