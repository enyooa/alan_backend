<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
// app/Http/Controllers/PlanController.php
class PlanController extends Controller
{
    /* GET /api/plans – все тарифы + их права */
    public function index()
    {
        return Plan::with(['permissions:id,code,name'])
                   ->orderBy('price')
                   ->get(['id','name','price','user_limit','slug']);
    }

    /* GET /api/my/plan – активный тариф организации пользователя */
    public function current(Request $r)
    {
        $plan = optional($r->user()->organization)->activePlan();
        return $plan ?: response()->json(null);
    }

    /* POST /api/my/plan/{plan} – сменить тариф (для superadmin’а) */
    public function change(Request $r, Plan $plan)
    {
        $org = $r->user()->organization;
        $org->plans()->sync([
            $plan->id => ['starts_at'=>now(), 'ends_at'=>null]
        ]);

        return ['success'=>true, 'plan'=>$plan];
    }
}
