<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    use HasFactory;

    protected $table = 'document_items';

    protected $fillable = [
        'document_id',
        'product_subcard_id',
        'unit_measurement',
        'quantity',
        'brutto',
        'netto',
        'price',
        'total_sum',
        'additional_expenses',
        'net_unit_weight',
        'cost_price' ,
    ];

    // ссылка на шапку
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    // ссылка на товар (если есть модель Item)
    public function product()          // ← раньше был ProductSubCard
    {
        return $this->belongsTo(ReferenceItem::class, 'product_subcard_id');
    }
    public function unitRef()
    {
        return $this->belongsTo(ReferenceItem::class, 'unit_measurement');
    }
}
