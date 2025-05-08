<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function store(Request $r, Organization $org)
{
    $plan = Plan::whereSlug($r->plan)->firstOrFail();

    // enforce user-limit right here if you need
    if ($plan->user_limit &&
        $org->users()->count() > $plan->user_limit) {
        return response()->json(['error'=>'User limit exceeded'], 422);
    }

    $org->plans()->attach($plan->id, [
        'starts_at'=>now(),
        'ends_at'  => now()->addDays($plan->period_days),
    ]);

    return response()->json(['success'=>true], 201);
}


public function buyPlan(Request $request)
    {
        $request->validate([
            'plan_slug' => 'required|string|exists:plans,slug',
        ]);

        $org  = $request->user()->organization;          // <— tenant org
        $plan = Plan::where('slug', $request->plan_slug)->firstOrFail();

        // close previous subscription if still active
        if ($org->activePlan()) {
            $org->activePlan()->pivot->update(['ends_at' => now()]);
        }

        // attach the new plan
        $org->plans()->attach($plan->id, [
            'starts_at' => now(),
            'ends_at'   => now()->addDays($plan->period_days),
        ]);

        // (optional) fire an event to sync permissions
        // PlanPurchased::dispatch($org, $plan);

        return response()->json([
            'message'   => 'Plan activated',
            'plan'      => $plan->name,
            'expiresAt' => now()->addDays($plan->period_days)->toDateTimeString(),
        ]);
    }

}
