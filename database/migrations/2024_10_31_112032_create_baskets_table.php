<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBasketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // корзина клиента
        Schema::create('baskets', function (Blueprint $table) {
            $table->id();
            $table->integer('id_client_request' );
            $table->date('delivery_date')->nullable();
            $table->integer('product_subcard_id');
            $table->string('source_table')->nullable();

            $table->integer('quantity')->default(1); // Quantity of the product

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
        Schema::dropIfExists('baskets');
    }
}
