<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PhoneVerification extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable = [
        'phone_number',
        'code',
        'organization_id'
    ];
    protected static function booted()
    {
        static::creating(fn ($org) => $org->id ??= (string) Str::uuid());
    }
}
