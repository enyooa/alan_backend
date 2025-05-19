<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;               // ← add this

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'surname',
        'whatsapp_number',
        'password',
        'summary',
        'address',
        'photo',
        'organization_id',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->{$user->getKeyName()})) {
                $user->{$user->getKeyName()} = Str::uuid()->toString();
            }
        });
    }
    /**
     * Define a many-to-many relationship with the Role model.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->roles->contains('name', $role);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function hasAnyRole(array $roles)
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Assign a role to the user.
     *
     * @param string $role
     * @return void
     */
    public function assignRole($role)
    {
        $roleInstance = Role::where('name', $role)->first();
        if ($roleInstance && !$this->roles->contains($roleInstance->id)) {
            $this->roles()->attach($roleInstance);
        }
    }

    /**
     * Remove a role from the user.
     *
     * @param string $role
     * @return void
     */
    public function removeRole($role)
    {
        $roleInstance = Role::where('name', $role)->first();
        if ($roleInstance) {
            $this->roles()->detach($roleInstance);
        }
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a client.
     *
     * @return bool
     */
    public function isClient()
    {
        return $this->hasRole('client');
    }

    /**
     * Relationship: Many addresses for this user.
     */
    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'address_user');
    }

    /**
     * Relationship: Many courier documents for this user.
     */

   public function permissions()                    // GRANT / DENY flag
{
    return $this->belongsToMany(Permission::class, 'permission_user')
                ->withPivot('allowed')           // boolean
                ->using(\App\Models\Pivot\PermissionUser::class);
}

/* проверить наличие операции */
public function hasPermission($code): bool
{
    return $this->allPermissions()->contains('code', $code);
}


/* назначить */
public function givePermission($code, bool $allow = true): void
{
    $permId = Permission::where('code', $code)->value('id');
    if ($permId) {
        $this->permissions()->syncWithoutDetaching([
            $permId => ['allowed' => $allow],
        ]);
    }
}

public function hasPermissionDeep(string $code): bool
{
    if ($this->hasPermission($code)) {
        return true;                                        // direct
    }

    return $this->organization
        ? $this->organization->planHasPermission($code)     // via plan
        : false;
}

/* снять */
public function revokePermission($code): void
{
    $permId = Permission::where('code', $code)->value('id');
    if ($permId) {
        $this->permissions()->detach($permId);
    }
}
public function organization()
{
    return $this->belongsTo(Organization::class);
}


    /* ───── accessor: $user->all_permission_codes ───── */
public function getAllPermissionCodesAttribute()
{
    // ① direct permissions
    $direct = $this->permissions()->pluck('code');

    // ② through plans of the user’s organization
    $planCodes = $this->organization
        ? $this->organization->plans()
              ->with('permissions:code')       // eager-load
              ->get()
              ->pluck('permissions')
              ->flatten()
              ->pluck('code')
        : collect();

    return $direct->merge($planCodes)->unique()->values();
}
// User.php
// app/Models/User.php
public function allPermissionCodes(): array
{
    return $this->allPermissions()
                ->pluck('code')
                ->values()
                ->all();
}



// app/Models/User.php
// app/Models/User.php
public function allPermissions(): \Illuminate\Support\Collection
{
    $planPerms = collect();
    if ($this->organization && $this->organization->active_plan) {
        $planPerms = $this->organization->active_plan->permissions;
    }

    $rolePerms = $this->roles->flatMap(function ($r) {
        return $r->permissions;
    });

    $overrides = $this->permissions;             // pivot->allowed
    $grant = $overrides->where('pivot.allowed', true);
    $deny  = $overrides->where('pivot.allowed', false);

    return $planPerms
            ->merge($rolePerms)
            ->merge($grant)
            ->diff($deny)                        // DENY wins
            ->unique('id')
            ->values();
}

}
