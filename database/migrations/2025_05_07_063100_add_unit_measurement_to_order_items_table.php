<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitMeasurementToOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // «единица измерения» – достаточно строки
            $table->string('unit_measurement', 255)
                  ->nullable()
                  ->after('price');

            // сумма по строке (qty * price)
            $table->decimal('totalsum', 15, 2)
                  ->nullable()
                  ->after('unit_measurement');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_measurement', 'totalsum']);
        });
    }
}
