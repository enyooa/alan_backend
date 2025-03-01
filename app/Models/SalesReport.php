<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    use HasFactory;

    protected $table = 'sales_reports';

    protected $fillable = [
        'quantity',
        'sale_amount',
        'cost_price',
        'profit',
        'report_date',
    ];
}
