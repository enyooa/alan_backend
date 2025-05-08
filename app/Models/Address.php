<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Address extends Model
{
    use HasFactory;
    protected $fillable = ['name','organization_id'];
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
    public function users()
    {
        return $this->belongsToMany(User::class, 'address_user');
    }


}
