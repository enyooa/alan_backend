<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Permission;

class SuperadminPermissionSeeder extends Seeder
{
    /**
     * ID суперадмина (из условия — 166)
     */
    const SUPERADMIN_ID = 166;

    public function run(): void
    {
        $user = User::find(self::SUPERADMIN_ID);

        if (!$user) {
            $this->command->error('User #'.self::SUPERADMIN_ID.' not found');
            return;
        }

        // все существующие permissions
        $permissionIds = Permission::pluck('id')->toArray();

        // привязываем *все* операции (не дублируя существующие)
        $user->permissions()->syncWithoutDetaching($permissionIds);

        $this->command->info('All permissions assigned to superadmin #'.self::SUPERADMIN_ID);
    }
}
