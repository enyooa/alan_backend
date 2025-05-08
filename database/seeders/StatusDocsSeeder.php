<?php

namespace Database\Seeders;

use App\Models\StatusDoc;
use Illuminate\Database\Seeder;

class StatusDocsSeeder extends Seeder
{
    public function run()
    {
        collect([
            'ожидание',      // pending
            'на фасовке',    // packing
            'Передано курьеру',      // delivering
            'исполнено',     // done
        ])->each(fn ($name) => StatusDoc::create(['name' => $name]));
    }
}
