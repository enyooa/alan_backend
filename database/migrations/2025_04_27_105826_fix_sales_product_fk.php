<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixSalesProductFk extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // 1) снимаем старый FK и индекс
            $table->dropForeign(['product_subcard_id']);

            // 2) если нужно переименовать колонку:
            // $table->renameColumn('product_subcard_id', 'reference_item_id');

            // 3) вешаем новый FK на reference_items
            $table->foreign('product_subcard_id')          // или 'reference_item_id'
                  ->references('id')
                  ->on('reference_items')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);   // или 'reference_item_id'
            // вернуть старый FK при откате:
            $table->foreign('product_subcard_id')
                  ->references('id')
                  ->on('product_sub_cards')
                  ->cascadeOnDelete();
        });
    }
}
