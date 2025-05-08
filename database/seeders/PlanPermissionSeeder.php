<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $map = [
            'client' => [1104,1103,1113],   // Requests, Sales, Debt report
            'intermediary' => [
                1101,1102,1103,1104,1106,1108,
                1110,1111,1112,1113,
                1114,1115,1116,1117,1118,1119
            ],
            'retail' =>      [1102,1103,1104,1105,1106,1107,1108,1109,1110,1111,1112,1113,1114,1115,1116,1117,1118,1119],
            'wholesaler' =>  [1102,1103,1104,1105,1106,1107,1108,1109,1110,1111,1112,1113,1114,1115,1116,1117,1118,1119],
            'grands' => Permission::pluck('code')->all(),          // всё
        ];

        foreach ($map as $slug=>$codes) {
            $plan = Plan::whereSlug($slug)->first();
            $permIds = Permission::whereIn('code',$codes)->pluck('id');
            $plan->permissions()->sync($permIds);
        }
    }
}
