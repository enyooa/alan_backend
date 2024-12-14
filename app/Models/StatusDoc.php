<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusDoc extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name'
    ];
}
