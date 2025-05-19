<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class ExpenseName extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'organization_id'];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (! $m->getKey()) $m->{$m->getKeyName()} = (string) Str::uuid();
        });
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
