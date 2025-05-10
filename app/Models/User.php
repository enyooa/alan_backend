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

     public function permissions()
     {
         return $this->belongsToMany(
             Permission::class,
             'permission_user'
         )->using(\App\Models\Pivot\PermissionUser::class);
     }

/* проверить наличие операции */
public function hasPermission($code)
{
    return $this->permissions->contains('code', $code);
}

/* назначить */
public function givePermission($code)
{
    $perm = Permission::where('code',$code)->first();
    if ($perm && !$this->hasPermission($code)) {
        $this->permissions()->attach($perm->id);
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
public function revokePermission($code)
{
    $perm = Permission::where('code',$code)->first();
    if ($perm) {
        $this->permissions()->detach($perm->id);
    }
}

public function organization()  // <- will be eager-loaded in responses
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
    // 1) личные права пользователя
    $codes = $this->permissions()->pluck('code');

    // 2) права, полученные через роли
    $codes = $codes->merge(
        $this->roles
             ->flatMap(function ($role) {          // для каждой роли…
                 return $role->permissions
                             ->pluck('code');      // …берём её codes
             })
    );

    // 3) права активного плана организации (если она есть)
    if ($this->organization) {
        $codes = $codes->merge(
            $this->organization->permissions->pluck('code')
        );
    }

    // убираем дубликаты и возвращаем как обычный массив
    return $codes->unique()->values()->all();
}


// app/Models/User.php
// app/Models/User.php
public function allPermissions()
{
    // 1) прямые
    $direct = $this->permissions()
        ->select('permissions.id', 'permissions.code', 'permissions.name')
        ->get();

    // 2) от ролей
    $byRoles = $this->roles()
        ->with(['permissions' => function ($q) {
            $q->select('permissions.id', 'permissions.code', 'permissions.name');
        }])
        ->get()
        ->pluck('permissions')
        ->flatten(1);

    // 3) от активного тарифа организации
    $planPerms = collect();
    if ($this->organization && $this->organization->activePlan()) {
        $planPerms = $this->organization
                          ->activePlan()
                          ->permissions()
                          ->select('permissions.id', 'permissions.code', 'permissions.name')
                          ->get();
    }

    return $direct->merge($byRoles)->merge($planPerms)
                  ->unique('id')->values();      // коллекция Permission
}


}
