<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Unit_measurement extends Model
{
    use HasFactory;
// Specify the table name if it doesn't follow the default convention
protected $table = 'unit_measurements';
protected $casts = [
    'tare' => 'float',
];
// Allow mass assignment
protected $fillable = ['name','tare','organization_id'];
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

}
