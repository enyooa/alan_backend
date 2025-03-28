<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusDocsSeeder extends Seeder
{
    public function run()
    {
        DB::table('status_docs')->insert([
            ['name' => 'ожидание'],     // or 'pending'
            ['name' => 'на фасовке'],   // or 'packing'
            ['name' => 'доставка'],     // or 'delivering'
            ['name' => 'исполнено'],    // or 'done'
        ]);
    }
}
