<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Permission;

class SuperadminPermissionSeeder extends Seeder
{
    public const SUPERADMIN_ID = '6622c9be-6378-4114-93c4-9a411ebd6e15';

    public function run(): void
    {
        /* ── ищем суперадмина ─────────────────────────────────────── */
        $user = User::find(self::SUPERADMIN_ID);

        if (! $user) {
            $this->command->error('⛔  User '.self::SUPERADMIN_ID.' not found');
            return;
        }

        /* ── мапим permission_id → [pivot-id] ─────────────────────── */
        $pivotData = Permission::pluck('id')
            ->mapWithKeys(fn ($pid) => [
                $pid => ['id' => (string) Str::uuid()]   // 👈 даём UUID
            ])
            ->toArray();

        /* ── attach / sync ─────────────────────────────────────────── */
        $user->permissions()->syncWithoutDetaching($pivotData);

        $this->command->info('✅  Все права назначены суперадмину');
    }
}
