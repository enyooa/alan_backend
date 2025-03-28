<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    use HasFactory;

    protected $table = 'warehouse_items';

    protected $fillable = [
        'warehouse_id',
        'product_subcard_id',
        'unit_measurement',
        'quantity',
        'brutto',
        'netto',
        'price',
        'total_sum',
        'additional_expenses',
        'cost_price' ,       
    ];

    // ссылка на шапку
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // ссылка на товар (если есть модель Item)
    public function product()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }
}
