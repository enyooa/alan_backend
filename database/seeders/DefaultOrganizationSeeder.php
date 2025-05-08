<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Permission;
use App\Models\User;

class DefaultOrganizationSeeder extends Seeder
{
    public function run()
    {
        // 1) Create or get the default organization
        $org = Organization::firstOrCreate(
            ['id' => Str::uuid()->toString(), 'name' => 'Default Organization'],

            [
                'id'                 => Str::uuid()->toString(),
                'address'            => '',
                'account'            => '',
                'manager_first_name' => '',
                'manager_last_name'  => '',
                'manager_phone'      => '',
                'manager_role'       => '',
            ]
        );

        // 2) Create or get the Starter plan
        $plan = Plan::firstOrCreate(
            ['id' => Str::uuid()->toString(), 'slug' => 'starter'],
            [
                'id'          => Str::uuid()->toString(),
                'name'        => 'Starter',
                'price'       => 0,
                'period_days' => 30,
                'user_limit'  => 100, // unlimited
            ]
        );

        // 3) Seed plan_permission pivot with ALL permissions
        //    (or filter: Permission::whereIn('code',['order.view','warehouse.create'])->pluck('id'))
        $allPermissionIds = Permission::pluck('id')->all();
        $plan->permissions()->sync($allPermissionIds);

        // 4) Attach the plan to the organization (organization_plan pivot)
        $org->plans()->syncWithPivotValues(
            [$plan->id],
            [
                'starts_at' => now(),
                'ends_at'   => now()->addDays($plan->period_days),
            ]
        );

        // 5) (Optional) assign your existing Super-Admin user to this org
        //    adjust the query to select your admin(s)
        $admin = User::where('first_name', 'Super')->first();
        if ($admin) {
            $admin->organization()->associate($org);
            $admin->save();
            $this->command->info("Assigned Super Admin ({$admin->id}) to Default Organization.");
        }

        $this->command->info("Default Organization ({$org->id}) seeded, Plan ({$plan->id}) attached with permissions.");
    }
}
