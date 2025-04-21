<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions'; // If needed

    use HasFactory;
    protected $fillable = [
        'name',
        'code',

    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_user');
    }
}
