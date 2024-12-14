<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'status',
        'address',
        'packer_id',
    ];
    public function packer()
{
    return $this->belongsTo(User::class, 'packer_id');
}
public function orderProducts()
{
    return $this->hasMany(OrderItem::class, 'order_id');
}
}
