<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtReport extends Model
{
    use HasFactory;

    protected $table = 'debt_reports';

    protected $fillable = [
        'counterparty_name',
        'start_balance_debt',
        'incoming_debt',
        'outgoing_debt',
        'end_balance_debt',
        'report_date',
    ];
}
