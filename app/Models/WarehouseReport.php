<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReport extends Model
{
    use HasFactory;

    protected $table = 'warehouse_reports';

    protected $fillable = [
        'warehouse_name',
        'start_balance',
        'arrival',
        'consumption',
        'end_balance',
        'total_sum',
        'report_date',
    ];
}
