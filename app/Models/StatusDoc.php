<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class StatusDoc extends Model
{
    use HasFactory;
    protected $table = 'status_docs';
    protected $fillable = [
        'name',
        'organization_id'
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
}
