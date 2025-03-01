<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminCashes extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'admin_id',
        'name',
        'IBAN'
    ];
}
