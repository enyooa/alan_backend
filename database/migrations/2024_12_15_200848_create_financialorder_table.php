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
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            $table->foreignUuid('user_id')
              ->references('id')->on('users')
              ->cascadeOnDelete();
            $table->string('type');
            $table->foreignUuid('admin_cash_id')
            ->constrained('admin_cashes')
            ->cascadeOnDelete();
            $table->foreignUuid('product_subcard_id')
            ->nullable()
            ->constrained('product_sub_cards')
            ->nullOnDelete();
            $table->foreignUuid('auth_user_id')->constrained('users')
            ->nullable();
            $table->foreignUuid('financial_element_id')
            ->constrained('financial_elements')
            ->cascadeOnDelete();


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
