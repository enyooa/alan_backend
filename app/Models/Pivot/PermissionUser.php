<?php
namespace App\Models\Pivot;         // ← точно так же, как в using()

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class PermissionUser extends Pivot
{
        protected $fillable   = ['allowed'];   // ← добавили

    public $incrementing = false;
    protected $keyType   = 'string';

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if (! $m->getKey()) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
