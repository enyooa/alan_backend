<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentItem extends Model
{
    use HasFactory;
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

    public function unit()
    {
        return $this->belongsTo(Unit_measurement::class,
                                'unit_measurement',   // FK в document_items
                                'name');              // колонка в unit_measurements
    }
    public function product()
    {
        return $this->belongsTo(ProductSubCard::class,
                                'product_subcard_id')
                    ->select('id','name','product_card_id');
    }
    public function unitByName()
    {
        return $this->belongsTo(Unit_measurement::class,
                                'unit_measurement',    // local key
                                'name',                // owner key
                                'unit');               // relation name
    }
    public function unit_measurements()
    {
        return $this->belongsTo(Unit_measurement::class,
                                'unit_measurement',    // local key
                                'name',                // owner key
                                'unit');               // relation name
    }
}
