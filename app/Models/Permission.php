<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    protected $table = 'permissions'; // If needed

    use HasFactory;
    protected $fillable = [
        'name',
        'code',
'organization_id'
    ];
    public $incrementing = false;
    protected $keyType   = 'string';
    protected static function booted()
    {
        static::creating(fn ($org) => $org->id ??= (string) Str::uuid());
    }
    public function users()
{
    return $this->belongsToMany(
        User::class,
        'permission_user'
    )->using(\App\Models\Pivot\PermissionUser::class);
}
}
