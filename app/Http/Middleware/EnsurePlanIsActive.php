<?php
// app/Http/Middleware/EnsurePlanIsActive.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePlanIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->hasRole('superadmin')) {
            return $next($request);
        }
        if ($user && $user->hasRole('client')) {
            return $next($request);
        }
        if ($user && $user->hasRole('packer')) {
            return $next($request);
        }
        if ($user && $user->hasRole('courier')) {
            return $next($request);
        }
        if ($user && $user->hasRole('cashbox')) {
            return $next($request);
        }
        if ($user && $user->hasRole('storager')) {
            return $next($request);
        }
        if ($user && $user->hasRole('admin')) {
            return $next($request);
        }
        // Allow artisan commands / unauth routes
        if (!$user)   return $next($request);

        $org  = $user->organization;

        if (!$org || !$org->activePlan()) {
            return response()->json([
                'message' => 'Подписка истекла или не оформлена'
            ], 402);                                 // 402 Payment Required
        }

        // (optional) enforce user-limit
        $limit = $org->activePlan()->user_limit;     // null == unlimited
        if ($limit && $org->users()->count() > $limit) {
            return response()->json([
                'message' => 'Лимит пользователей на тарифе исчерпан'
            ], 403);
        }

        return $next($request);
    }
}
