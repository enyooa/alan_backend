<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    //Ценовое предложение для клиента
    public function up()
    {
        // ценовое предложение
        Schema::create('price_requests', function (Blueprint $table) {
            $table->id();
            $table->string('choice_status')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('address_id')->nullable();
            $table->integer('product_subcard_id');
            $table->string('unit_measurement')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('price')->nullable();
            $table->date('start_date')->nullable(); // Add start date
            $table->date('end_date')->nullable();  
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
        Schema::dropIfExists('price_requests');
    }
}
