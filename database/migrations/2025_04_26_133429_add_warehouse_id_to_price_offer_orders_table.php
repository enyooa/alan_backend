<?php
// database/migrations/2025_04_26_000001_add_warehouse_id_to_price_offer_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_offer_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')
                  ->nullable()                 // drop “nullable()” if it must always be present
                  ->after('address_id');

            $table->foreign('warehouse_id')
                  ->references('id')
                  ->on('warehouses')
                  ->cascadeOnDelete();         // or ->restrictOnDelete()
        });
    }

    public function down(): void
    {
        Schema::table('price_offer_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
