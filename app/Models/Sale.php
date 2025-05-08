<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Sale extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable = [
        'client_id','warehouse_id','sale_date','total_sum','organization_id'
    ];
    protected static function boot()
    {
        parent::boot();                       // ← не забываем!

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    public function items()      { return $this->hasMany(SaleItem::class); }
    public function client()     { return $this->belongsTo(User::class);   }
    public function warehouse()  { return $this->belongsTo(Warehouse::class); }
    public function organization()
{
    return $this->belongsTo(Organization::class);
}

}
