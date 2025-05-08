<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')
            ->constrained('warehouses')
            ->cascadeOnDelete();            // вместо admin_warehouses.* , теперь:
            $table->foreignUuid('product_subcard_id')
            ->constrained('product_sub_cards')
            ->cascadeOnDelete();
            $table->string('unit_measurement')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('brutto', 15, 3)->default(0);
            $table->decimal('netto', 15, 3)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total_sum', 15, 2)->default(0);
            $table->decimal('additional_expenses', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);

            // foreign
            // $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            // product_subcard_id -> product_subcards.id ?
            // $table->foreign('product_subcard_id')->references('id')->on('product_subcards');
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
        Schema::dropIfExists('warehouse_items');
    }
}
