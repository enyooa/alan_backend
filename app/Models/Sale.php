<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'product_subcard_id',   // теперь указывает на reference_items.id
        'unit_measurement',
        'amount',
        'price',
    ];

    /*––  новое canonical-имя  ––*/
    public function product()        // под-карточка-товара
    {
        return $this->belongsTo(ReferenceItem::class, 'product_subcard_id');
    }

    /*––  старое имя, чтобы не падал legacy-код  ––*/
    public function subCard()
    {
        return $this->product();
    }
}
