<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTareToUnitMeasurements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add the tare column to the unit_measurements table
        Schema::table('unit_measurements', function (Blueprint $table) {
            $table->double('tare')->nullable()->after('name');  // Change 'after' to place the column where you'd like
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the tare column if rolling back
        Schema::table('unit_measurements', function (Blueprint $table) {
            $table->dropColumn('tare');
        });
    }
}
