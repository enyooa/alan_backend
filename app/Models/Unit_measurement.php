<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit_measurement extends Model
{
    use HasFactory;
// Specify the table name if it doesn't follow the default convention
protected $table = 'unit_measurements';

// Allow mass assignment
protected $fillable = ['name'];
    
}
