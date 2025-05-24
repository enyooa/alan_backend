<?php
// app/Http/Middleware/EnsurePermissionsInPlan.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnsurePermissionsInPlan
{
    public function handle(Request $r, Closure $next)
    {
        $actor = $r->user();                    // caller of the API
        if ($actor->hasRole('superadmin')) {    // superadmin bypass
            return $next($r);
        }

        $org   = $actor->organization;          // may be null…
        $codes = $org ? $org->planPermissionCodes() : [];

        // ---- permissions array ------------------------------------------------
        $reqPerms = collect($r->input('permissions', []))->unique();
        $invalid  = $reqPerms->diff($codes);
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' =>
                    'Нельзя назначать коды, которых нет в тарифе организации: '
                    .$invalid->implode(', ')
            ]);
        }

        // ---- roles array (optional) ------------------------------------------
        $reqRoles = collect($r->input('roles', []))->unique();
        if ($reqRoles->isNotEmpty()) {
            $badRole = \App\Models\Role::whereIn('name',$reqRoles)
                        ->get()
                        ->first(fn ($role) => ! $role->allowedForPlan($codes));
            if ($badRole) {
                throw ValidationException::withMessages([
                    'roles' => "Роль «{$badRole->name}» содержит права вне тарифа."
                ]);
            }
        }

        return $next($r);
    }
}
