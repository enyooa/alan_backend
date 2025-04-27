<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'account',
        'manager_first_name',
        'manager_last_name',
        'manager_phone',
        'manager_role',        // or manager_role_id
    ];


}
