<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackerDocument extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id_courier',
        'delivery_address',
        // 'product_subcard_id',
        // 'amount_of_products',
    ];
    public function courier()
    {
        return $this->belongsTo(User::class, 'id_courier');
    }

    public function productSubcard()
    {
        return $this->belongsTo(ProductSubcard::class, 'product_subcard_id');
    }

    public function orderItems()
{
    return $this->hasMany(OrderItem::class, 'packer_document_id');
}

}
