<?php
// app/Models/PhoneVerification.php
// app/Models/PhoneVerification.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PhoneVerification extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    /* Сделайте либо так: */
    protected $fillable = ['phone_number', 'code', 'organization_id'];
    //  id заполняется в хуке creating; timestamps — по-умолчанию

    /* или вообще откройте всё: */
    // protected $guarded = [];   // ничего не запрещаем

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            if (!$m->getKey()) {
                $m->id = (string) Str::uuid();
            }
        });
    }
}

