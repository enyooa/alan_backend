<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // полный набор кодов (добавьте/уберите по необходимости)
        $ops = [
            1101 => ['Поступления ТМЗ',          'Receipts of inventory'],
            1102 => ['Накладные',                'Invoices'],
            1103 => ['Продажи',                  'Sales'],
            1104 => ['Заявки',                   'Requests'],
            1105 => ['Ценовые предложения',      'Price offers'],
            1106 => ['Приходный ордер',          'Receipt order'],
            1107 => ['Расходный ордер',          'Expense order'],
            1108 => ['Списания товаров',         'Write-offs of goods'],
            1109 => ['Инвентаризация',           'Inventory'],
            1110 => ['Отчёт по кассе',           'Cash report'],
            1111 => ['Отчёт по продажам',        'Sales report'],
            1112 => ['Отчёт по складу',          'Warehouse report'],
            1113 => ['Отчёт по долгам',          'Debt report'],
            1114 => ['Карточки',                 'Cards'],
            1115 => ['Подкарточки',              'Subcards'],
            1116 => ['Ед. измерения',            'Unit of measurement'],
            1117 => ['Поставщик',                'Supplier'],
            1118 => ['Статья прихода',           'Receipt item'],
            1119 => ['Статья расхода',           'Expense item'],

            /* 1200-я серия */
            1201 => ['Адрес',                    'Address'],
            1202 => ['Фин. элемент (Приход)',    'Financial element Income'],
            1203 => ['Фин. элемент (Расход)',    'Financial element Expense'],
            1204 => ['Перемещение',              'Transfer'],
            1205 => ['Продажи клиенту',          'Client sales'],

            /* Чат (новые) */
            1206 => ['Чат (чтение)',             'Chat read'],
            1207 => ['Чат (отправка)',           'Chat send'],
        ];

        foreach ($ops as $code => [$ru, $en]) {
            Permission::updateOrCreate(
                ['code' => $code],               // уникальный ключ
                ['name' => "$ru / $en"]          // поля для обновления
            );
        }
    }
}
