<?php
// database/migrations/xxxx_xx_xx_alter_sale_items_fk_to_cascade.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // 1) удаляем старый FK
            $table->dropForeign('sale_items_product_subcard_id_foreign');

            // 2) создаём новый – с каскадом
            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);

            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->onDelete('restrict');      // или NO ACTION
        });
    }
};
