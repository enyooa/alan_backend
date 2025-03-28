<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_offers_items', function (Blueprint $table) {
            $table->id();

            $table->integer('price_offer_order_id')->nullable();

            $table->integer('product_subcard_id');
            $table->string('unit_measurement')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('price')->nullable();


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
        Schema::dropIfExists('price_offers');
    }
}
