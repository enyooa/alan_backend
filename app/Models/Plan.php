<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Plan extends Model
{
    use HasFactory;

    public $incrementing = false;       // ← add
    protected $keyType   = 'string';

    protected $fillable = [
        'slug','name','price','period_days','user_limit','organization_id'
    ];
    protected static function booted()
    {
        static::creating(fn ($plan) => $plan->id ??= (string) Str::uuid());
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'plan_permission');
    }
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_plan')
                    ->withPivot('starts_at', 'ends_at')
                    ->withTimestamps();
    }

}
