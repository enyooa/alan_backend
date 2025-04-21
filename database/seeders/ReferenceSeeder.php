<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reference;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // ➊ Карточка (главная)
        $card = Reference::create([
            'name'    => 'Карточка',
            'card_id' => null,
        ]);

        // ➋ Подкарточка (дочерняя)
        Reference::create([
            'name'    => 'Подкарточка',
            'card_id' => $card->id,   // parent → “Карточка”
        ]);
    }
}
