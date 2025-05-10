<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Organization extends Model
{
    use HasFactory;

    public $incrementing = false;      // ← add
    protected $keyType   = 'string';   // ← add

    protected $fillable = [
        'name',
        'address',
        'account',
        'manager_first_name',
        'manager_last_name',
        'manager_phone',
        'manager_role',        // or manager_role_id
    ];

    protected static function booted()
    {
        static::creating(fn ($org) => $org->id ??= (string) Str::uuid());
    }
    public function activePlan()      // returns a single Plan model
    {
        return $this->belongsToMany(Plan::class, 'organization_plan')
                    ->wherePivot('starts_at','<=',now())
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at','>',now());
                    })
                    ->withPivot('starts_at','ends_at')
                    ->first();
    }
    public function planHasPermission(string $code): bool
    {
        $plan = $this->activePlan();
        if (!$plan) return false;

        return $plan->permissions()->where('code',$code)->exists();
    }
    public function users()        { return $this->hasMany(User::class); }
    public function plans()
    {
        /* имя pivot-таблицы  organization_plan  */
        return $this->belongsToMany(Plan::class, 'organization_plan')
                    ->withPivot('starts_at', 'ends_at')
                    ->withTimestamps();
    }
// app/Models/Organization.php
public function getPermissionsAttribute()
{
    $activePlan = $this->plans()
        ->wherePivot('starts_at', '<=', now())
        ->where(function ($q) {
            $q->whereNull('organization_plan.ends_at')
              ->orWhere('organization_plan.ends_at', '>=', now());
        })
        ->first(); // may be null

    // replace `$activePlan?->permissions ?? collect();`
    return optional($activePlan)->permissions ?: collect();
}



}
