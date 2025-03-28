<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
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

public function packerDocument()
{
    return $this->belongsTo(PackerDocument::class, 'packer_document_id');
}

public function courierDocument()
{
    return $this->belongsTo(CourierDocument::class, 'courier_document_id');
}

}
