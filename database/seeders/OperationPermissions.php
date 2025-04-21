<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;          // If using DB::table()
use App\Models\Permission;                  // If using the Eloquent model

class OperationPermissions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define all the permissions you want to seed
        $permissions = [
            [
                'name' => 'Отчёты',            // e.g. "Reports"
                'code' => 1002,
            ],
            [
                'name' => 'Накладные',         // e.g. "Invoices"
                'code' => 1003,
            ],
            [
                'name' => 'Заявки',            // e.g. "Requests"
                'code' => 1004,
            ],
            [
                'name' => 'Продажи',           // e.g. "Sales"
                'code' => 1005,
            ],
        ];

        // Option A: Use the Permission Eloquent model
        foreach ($permissions as $perm) {
            Permission::create($perm);
        }

        // --- or ---

        // Option B: Insert directly with DB facade
        /*
        DB::table('permissions')->insert($permissions);
        */
    }
}
