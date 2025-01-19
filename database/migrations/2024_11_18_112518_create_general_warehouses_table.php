<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         // склады общие 
        Schema::create('general_warehouses', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('product_subcard_id');
            $table->integer('user_id')->nullable();
            $table->integer('address_id')->nullable();

            $table->string('unit_measurement')->nullable();// ед измерение
            $table->double('quantity')->nullable(); // количества
            $table->integer('price')->nullable();//цена
            $table->integer('total_sum')->nullable();//итог
            $table->date('date')->nullable();
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
        Schema::dropIfExists('general_warehouses');
    }
}
