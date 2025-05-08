<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug'=>'client',       'name'=>'Client',       'price'=>0,       'period_days'=>0,   'user_limit'=>0  ],
            ['slug'=>'intermediary', 'name'=>'Intermediary', 'price'=>100000,  'period_days'=>30,  'user_limit'=>5  ],
            ['slug'=>'retail',       'name'=>'Retail',       'price'=>200000,  'period_days'=>30,  'user_limit'=>10 ],
            ['slug'=>'wholesaler',   'name'=>'Wholesaler',   'price'=>500000,  'period_days'=>30,  'user_limit'=>50 ],
            ['slug'=>'grands',       'name'=>'Grands',       'price'=>1000000, 'period_days'=>30,  'user_limit'=>null],
        ];

        foreach ($rows as $row) {
            Plan::updateOrCreate(['slug'=>$row['slug']], $row);
        }
    }
}
