<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceUnitToFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('product_subcard_id');
            $table->string('unit_measurement', 255)->nullable()->after('price');
            $table->decimal('totalsum', 15, 2)->nullable()->after('unit_measurement');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn(['price', 'unit_measurement', 'totalsum']);
        });
    }
}
