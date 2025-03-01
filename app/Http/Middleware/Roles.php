<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class Roles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
{
    // \Log::info('Middleware Debug', [
    //     'user_id' => auth()->id(),
    //     'user_roles' => auth()->check() ? auth()->user()->roles->pluck('name')->toArray() : null,
    //     'required_roles' => $roles,
    // ]);

    if (!auth()->check()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    if (!auth()->user()->hasAnyRole($roles)) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return $next($request);
}





}
