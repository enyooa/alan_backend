<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Expense extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $table = 'expenses'; // If your table name is "expenses"

    protected $fillable = [
        'name',
        'organization_id',
        'provider_id',
        'document_id',
        'amount',

    ];
    protected static function booted()
    {
        static::creating(function ($s) {
            if (empty($role->{$s->getKeyName()})) {
                $s->{$s->getKeyName()} = Str::uuid()->toString();
            }
        });
    }
    // (optional) The inverse relationship if you want it:
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

     /* ② строка-справочник, в которой хранится «Бензин», «Дизель» и т.п.  */

     /* ③ поставщик, от кого эта услуга / расход */
     public function provider()       { return $this->belongsTo(Provider::class); }

}
