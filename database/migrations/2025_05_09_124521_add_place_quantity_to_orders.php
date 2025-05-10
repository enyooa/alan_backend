<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlaceQuantityToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->unsignedInteger('place_quantity')
              ->default(0)
              ->after('courier_id');   // поставьте в удобное место
    });
}

public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('place_quantity');
    });
}

}
