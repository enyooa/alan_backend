<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // единица измерения
        Schema::create('unit_measurements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('tare')->nullable()->after('name');  // Change 'after' to place the column where you'd like
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_measurements');
    }
}
