<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')
            ->constrained('orders')
            ->cascadeOnDelete();
            $table->foreignUuid('product_subcard_id')
            ->constrained('product_sub_cards')
            ->cascadeOnDelete();
            $table->string('source_table'); // Source: 'sales' or 'price_requests'
            $table->integer('quantity')->default(1); // Quantity ordered
            $table->integer('price')->nullable(); // Price per unit
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
        Schema::dropIfExists('order_items');
    }
}
