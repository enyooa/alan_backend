<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

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
        'document_id'
    ];
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
    public function unit()              { return $this->belongsTo(Unit_measurement::class, 'unit_measurement_id'); }

}
