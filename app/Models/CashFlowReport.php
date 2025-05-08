<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CashFlowReport extends Model
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
    protected $table = 'cash_flow_reports';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cash_name',
        'start_balance',
        'incoming',
        'outgoing',
        'end_balance',
        'report_date',
    ];
}
