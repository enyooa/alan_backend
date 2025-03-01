<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id', 
        'amount_of_products',
        'is_confirmed'
    ];

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'courier_document_id');
    }
    public function documentProducts()
    {
        return $this->hasMany(CourierDocumentProduct::class, 'courier_document_id');
    }
    public function getOrdersWithClients()
{
    return $this->hasManyThrough(
        Order::class,
        OrderItem::class,
        'courier_document_id', // Foreign key on the order_items table
        'id', // Foreign key on the orders table
        'id', // Local key on the courier_documents table
        'order_id' // Local key on the order_items table
    );
}

public function clients()
{
    return $this->belongsToMany(User::class, 'client_courier_documents', 'courier_document_id', 'client_id');
}

}
