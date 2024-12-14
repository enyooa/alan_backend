<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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

    /**
     * Check if the user has any of the given roles.
     *
     * @param array $roles
     * @return bool
     */
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

    public function addresses()
{
    return $this->belongsToMany(Address::class, 'address_user');
}

}
