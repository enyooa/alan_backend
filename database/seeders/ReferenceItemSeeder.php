<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reference;

class ReferenceItemSeeder extends Seeder
{
    public function run(): void
    {
        $card     = Reference::where('name', 'Карточка')->first();
        $subCard  = Reference::where('name', 'Подкарточка')->first();

        // Товары для “Карточки”
        $card->items()->createMany([
            ['name' => 'Помидор', 'value' => 0],
            ['name' => 'Огурцы',  'value' => 0],
            ['name' => 'Банан',   'value' => 0],
        ]);

        // Цвета для “Подкарточки”
        $subCard->items()->createMany([
            ['name' => 'Красный', 'value' => 0],
            ['name' => 'Зеленый', 'value' => 0],
            ['name' => 'Желтый',  'value' => 0],
        ]);
    }
}
