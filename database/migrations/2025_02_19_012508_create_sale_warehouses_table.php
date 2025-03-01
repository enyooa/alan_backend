<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_warehouses', function (Blueprint $table) {
            $table->id();
            $table->integer('product_subcard_id');
            $table->double('quantity')->nullable(); // количества
            $table->string('unit_measurement')->nullable();// ед измерение
            $table->double('brutto')->nullable();
            $table->double('netto')->nullable();
            $table->integer('price')->nullable();//цена
            $table->integer('total_sum')->nullable();//итог
            $table->double('cost_price')->nullable();

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
        Schema::dropIfExists('sale_warehouses');
    }
}
