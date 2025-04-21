<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    // app/Models/FinancialOrder.php
public function provider()
{
    return $this->belongsTo(Provider::class, 'provider_id');
}

}
