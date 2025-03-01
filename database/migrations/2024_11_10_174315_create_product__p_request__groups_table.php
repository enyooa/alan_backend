<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPRequestGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
                // Группа,заявка, накладная, поступление ценового предложения

                //product_price_request_groups
        Schema::create('product__p_request__groups', function (Blueprint $table) {
            $table->id();
            $table->integer('product_card_id');
            $table->integer('price_request_id');
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
        Schema::dropIfExists('product__p_request__groups');
    }
}
