<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // карточка товара
        Schema::create('product_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name_of_products');
            $table->string('description')->nullable();
            $table->string('country')->nullable();
            $table->string('type')->nullable();
            
            $table->string('photo_product')->nullable();
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
        Schema::dropIfExists('product_cards');
    }
}
