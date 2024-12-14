<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateToGeneralWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_warehouses', function (Blueprint $table) {
            $table->date('date')->nullable()->after('unit_measurement');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_warehouses', function (Blueprint $table) {
            $table->dropColumn('date');

        });
    }
}
