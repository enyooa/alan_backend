<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowReport extends Model
{
    use HasFactory;

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
