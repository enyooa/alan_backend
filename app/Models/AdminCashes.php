<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminCashes extends Model
{
    use HasFactory;
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
    protected $fillable = [
        'admin_id',
        'name',
        'IBAN',
        'organization_id'
    ];

    public function admin()        { return $this->belongsTo(User::class, 'admin_id'); }
    public function organization() { return $this->belongsTo(Organization::class); }
}
