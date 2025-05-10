<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlaceQuantityToOrderItemsTable extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // `place_quantity` — сколько мест/коробок/паллет занял товар
            $table->unsignedInteger('place_quantity')
                  ->default(0)
                  ->after('courier_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('place_quantity');
        });
    }
}
