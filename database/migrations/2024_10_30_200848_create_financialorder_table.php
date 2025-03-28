<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // создать приходной ордер
        Schema::create('financial_orders', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('admin_cash_id');
            $table->integer('product_subcard_id')->nullable(); // Ensure 'product_cards' table exists
            $table->integer('user_id'); // Ensure 'cashboxes' table exists
            $table->integer('financial_element_id'); // Ensure 'financial
            $table->unsignedBigInteger('auth_user_id');
            $table->integer('summary_cash');
            $table->date('date_of_check');
            $table->string('photo_of_check')->nullable();
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
        Schema::dropIfExists('financial_orders');
    }
}
