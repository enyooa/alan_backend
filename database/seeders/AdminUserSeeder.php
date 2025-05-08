<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // --- SUPERADMIN USER ---
        // Make sure a 'superadmin' role exists in your roles table before running this
        $superAdminUser = User::create([
            'first_name'      => 'Super',
            'last_name'       => 'Admin',
            'surname'         => 'Root',
            'whatsapp_number' => '7056055050',
            'password'        => Hash::make('Admin12345'), // choose your desired password
        ]);

        $superAdminUser->roles()->attach(Role::where('name', 'superadmin')->first());
        // Create Admin User
        $adminUser = User::create([
            'first_name'      => 'Ахат',
            'last_name'       => 'Админ',
            'surname'         => 'Админ',
            'whatsapp_number' => '7056055051',
            'password'        => Hash::make('Admin12345')
        ]);
        $adminUser->roles()->attach(Role::where('name', 'admin')->first());

        // Create Client User
        $clientUser = User::create([
            'first_name'      => 'Клиент',
            'last_name'       => 'Тест',
            'surname'         => 'Клиент',
            'whatsapp_number' => '7056055052',
            'password'        => Hash::make('Client12345')
        ]);
        $clientUser->roles()->attach(Role::where('name', 'client')->first());

        // Create Cashbox User
        $cashboxUser = User::create([
            'first_name'      => 'Кассир',
            'last_name'       => 'Тест',
            'surname'         => 'Кассир',
            'whatsapp_number' => '7056055053',
            'password'        => Hash::make('Cashbox12345')
        ]);
        $cashboxUser->roles()->attach(Role::where('name', 'cashbox')->first());

        // Create Packer User
        $packerUser = User::create([
            'first_name'      => 'Упаковщик',
            'last_name'       => 'Тест',
            'surname'         => 'Упаковщик',
            'whatsapp_number' => '7056055054',
            'password'        => Hash::make('Packer12345')
        ]);
        $packerUser->roles()->attach(Role::where('name', 'packer')->first());

        // Create Storage User
        $storageUser = User::create([
            'first_name'      => 'Склад',
            'last_name'       => 'Тест',
            'surname'         => 'Склад',
            'whatsapp_number' => '7056055055',
            'password'        => Hash::make('Storage12345')
        ]);
        $storageUser->roles()->attach(Role::where('name', 'storager')->first());

        // Create Courier User
        $courierUser = User::create([
            'first_name'      => 'Курьер',
            'last_name'       => 'Тест',
            'surname'         => 'Курьер',
            'whatsapp_number' => '7056055056',
            'password'        => Hash::make('Courier12345')
        ]);
        $courierUser->roles()->attach(Role::where('name', 'courier')->first());

        $this->command->info('Admin, Client, Cashbox, Packer, Storager, and Courier users created successfully!');
    }
}
