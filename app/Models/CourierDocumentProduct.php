<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierDocumentProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_document_id',
        'product_subcard_id',
        'quantity',
    ];

    public function courierDocument()
    {
        return $this->belongsTo(CourierDocument::class, 'courier_document_id');
    }

    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
}
