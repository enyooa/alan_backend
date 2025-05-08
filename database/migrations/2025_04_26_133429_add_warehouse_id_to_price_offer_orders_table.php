<?php
// database/migrations/2025_04_26_000001_add_warehouse_id_to_price_offer_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseIdToPriceOfferOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('price_offer_orders', function (Blueprint $table) {
            // Add a nullable UUID column, constraint it to warehouses.id, cascade on delete
            $table->foreignUuid('warehouse_id')
                  ->nullable()
                  ->after('address_id')
                  ->constrained('warehouses')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('price_offer_orders', function (Blueprint $table) {
            // This helper drops both the FK and the column
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
}
