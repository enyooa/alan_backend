<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Expense extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'provider_id',
        'document_id',
        'expense_name_id',
        'amount',
    ];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (! $m->getKey()) $m->{$m->getKeyName()} = (string) Str::uuid();
        });
    }

    public function name()     { return $this->belongsTo(ExpenseName::class, 'expense_name_id'); }
    public function document() { return $this->belongsTo(Document::class); }
    public function provider() { return $this->belongsTo(Provider::class); }
}
