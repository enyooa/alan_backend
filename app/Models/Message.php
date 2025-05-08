<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = ['user_id', 'message','organization_id'];
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class)
                    ->select('id', 'first_name', 'last_name')   //  only what chat needs
                    ->with('roles:id,name');                   // 👈 add this

    }
}
