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
            $table->uuid('id')->primary();

            $table->foreignUuid('price_offer_order_id')
              ->constrained('price_offer_orders')
              ->cascadeOnDelete();

            // product FK
            $table->foreignUuid('product_subcard_id')
              ->constrained('product_sub_cards')
              ->cascadeOnDelete();
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
