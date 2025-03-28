<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // User who placed the order

            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('address')->nullable(); // Delivery address
            $table->timestamp('shipped_at')->nullable(); // When the order was shipped
            $table->timestamp('delivered_at')->nullable(); // When the order was delivered
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
        Schema::dropIfExists('orders');
    }
}
