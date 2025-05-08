<?php
// database/seeders/SuperAdminSeeder.php

namespace Database\Seeders;

use App\Models\{Organization, Plan, Role, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        /*----------------------------------------------------
        |  1.  Организация-“root” (SuperOrg)
        *---------------------------------------------------*/
        $org = Organization::updateOrCreate(
            ['name' => 'SuperOrg'],                               // UNIQUE-ключ
            [
                'address'            => 'Алматы, ул. Достык, 1',
                'account'            => 'KZ00 SUPER 0000 0000 0000',
                'manager_first_name' => 'Super',
                'manager_last_name'  => 'Chief',
                'manager_phone'      => '+7 700 000 00 00',
                'manager_role'       => 'CEO',                    // или id роли
            ]
        );

        /*----------------------------------------------------
        |  2.  Супер-администратор
        *---------------------------------------------------*/
        $super = User::updateOrCreate(
            ['whatsapp_number' => '7056055050'],                // UNIQUE-ключ
            [
                'first_name'      => 'Super',
                'last_name'       => 'Admin',
                'password'        => Hash::make('Admin12345'),
                'organization_id' => $org->id,
            ]
        );

        // назначаем (или создаём) роль “superadmin”
        $super->assignRole(
            Role::firstOrCreate(['name' => 'superadmin'])->name
        );

        /*----------------------------------------------------
        |  3.  Вечная подписка (план “grands”)
        *---------------------------------------------------*/
        $plan = Plan::where('slug', 'grands')->first();           // должен существовать
        $org->plans()->syncWithoutDetaching([
            $plan->id => ['starts_at' => now(), 'ends_at' => null]
        ]);
    }
}
