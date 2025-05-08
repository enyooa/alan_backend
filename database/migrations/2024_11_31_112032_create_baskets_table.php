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
            $table->uuid('id')->primary();
            $table->foreignUuid('id_client_request')                // ← rename and use UUID
          ->constrained('users')
          ->cascadeOnDelete();
          $table->foreignUuid('organization_id')
          ->nullable()
          ->constrained('organizations')
          ->cascadeOnDelete();
            $table->date('delivery_date')->nullable();
            $table->foreignUuid('product_subcard_id')
            ->constrained('product_sub_cards')
            ->cascadeOnDelete();
            $table->string('source_table')->nullable();
            $table->string('unit_measurement')->nullable();

            $table->decimal('totalsum', 15, 2)->nullable();
            $table->integer('quantity'); // Quantity of the product

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
