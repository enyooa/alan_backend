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
        // Create Admin User
        $adminUser = User::create([
            'first_name' => 'Ахат',
            'last_name' => 'Админ',
            'surname' => 'Админ',
            'whatsapp_number' => '87056055051',
            'password' => Hash::make('Admin12345')
        ]);
        $adminUser->roles()->attach(Role::where('name', 'admin')->first());

        // Create Client User
        $clientUser = User::create([
            'first_name' => 'Клиент',
            'last_name' => 'Тест',
            'surname' => 'Клиент',
            'whatsapp_number' => '87056055052',
            'password' => Hash::make('Client12345')
        ]);
        $clientUser->roles()->attach(Role::where('name', 'client')->first());

        // Create Cashbox User
        $cashboxUser = User::create([
            'first_name' => 'Кассир',
            'last_name' => 'Тест',
            'surname' => 'Кассир',
            'whatsapp_number' => '87056055053',
            'password' => Hash::make('Cashbox12345')
        ]);
        $cashboxUser->roles()->attach(Role::where('name', 'cashbox')->first());

        // Create Packer User
        $packerUser = User::create([
            'first_name' => 'Упаковщик',
            'last_name' => 'Тест',
            'surname' => 'Упаковщик',
            'whatsapp_number' => '87056055054',
            'password' => Hash::make('Packer12345')
        ]);
        $packerUser->roles()->attach(Role::where('name', 'packer')->first());

        // Create Storage User
        $storageUser = User::create([
            'first_name' => 'Склад',
            'last_name' => 'Тест',
            'surname' => 'Склад',
            'whatsapp_number' => '87056055055',
            'password' => Hash::make('Storage12345')
        ]);
        $storageUser->roles()->attach(Role::where('name', 'storage')->first());

        // Create Courier User
        $courierUser = User::create([
            'first_name' => 'Курьер',
            'last_name' => 'Тест',
            'surname' => 'Курьер',
            'whatsapp_number' => '87056055056',
            'password' => Hash::make('Courier12345')
        ]);
        $courierUser->roles()->attach(Role::where('name', 'courier')->first());

        $this->command->info('Admin, Client, Cashbox, Packer, Storage, and Courier users created successfully!');
    }
}
