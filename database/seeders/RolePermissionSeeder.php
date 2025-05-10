<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin  = Role::firstWhere('name','superadmin');
        $codes  = [1101,1102,1103, /* … */ ];

        $ids = Permission::whereIn('code',$codes)->pluck('id');
        $admin->permissions()->sync($ids);
    }
}
