<?php

namespace Database\Seeders;

use App\Models\Reference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceAndItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /* ---------- 1. “Карточка” ---------- */
            $card = Reference::create([
                'name'    => 'Карточка',
                'card_id' => null,
            ]);

            $card->items()->createMany([
                ['name' => 'Помидор', 'value' => 0],
                ['name' => 'Огурцы',  'value' => 0],
                ['name' => 'Банан',   'value' => 0],
            ]);

            /* ---------- 2. “Подкарточка” ---------- */
            $subCard = Reference::create([
                'name'    => 'Подкарточка',
                'card_id' => $card->id,   // указываем родителя
            ]);

            $subCard->items()->createMany([
                ['name' => 'Красный', 'value' => 0],
                ['name' => 'Зелёный', 'value' => 0],
                ['name' => 'Жёлтый',  'value' => 0],
            ]);
        });
    }
}
