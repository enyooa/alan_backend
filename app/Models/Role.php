<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Role extends Model
{
    use HasFactory;

    // 1) Tell Eloquent your PK is a string, not an auto-increment integer
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name','organization_id'];

    protected static function booted()
    {
        static::creating(function ($role) {
            if (empty($role->{$role->getKeyName()})) {
                $role->{$role->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
    // app/Models/Role.php
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

}
