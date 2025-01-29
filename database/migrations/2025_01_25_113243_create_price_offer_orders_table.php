<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceOfferOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_offer_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_request_id');
            $table->unsignedBigInteger('product_subcard_id');
            $table->string('unit_measurement');
            $table->integer('amount');
            $table->decimal('price', 10, 2);
            $table->decimal('total', 12, 2);
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
        Schema::dropIfExists('price_offer_orders');
    }
}
