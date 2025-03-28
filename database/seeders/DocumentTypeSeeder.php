<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run()
    {
        // Можно использовать Eloquent:
        DocumentType::create([
            'code' => 'income',
            'name' => 'Приход',
            'description' => 'Поступление товара на склад',
        ]);

        DocumentType::create([
            'code' => 'transfer',
            'name' => 'Перемещение',
            'description' => 'Перемещение товара между складами',
        ]);

        DocumentType::create([
            'code' => 'sale',
            'name' => 'Продажа',
            'description' => 'Реализация/продажа товара',
        ]);

        DocumentType::create([
            'code' => 'write_off',
            'name' => 'Списание',
            'description' => 'Списание товара',
        ]);

        DocumentType::create([
            'code' => 'inventory',
            'name' => 'Инвентаризация',
            'description' => 'Проверка остатков на складе',
        ]);
    }
}
