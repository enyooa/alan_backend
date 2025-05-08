<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')
            ->references('id')->on('documents')
            ->onDelete('cascade');            // вместо admin_warehouses.* , теперь:
            $table->foreignUuid('product_subcard_id')
            ->constrained('product_sub_cards')
            ->cascadeOnDelete();

            $table->decimal('net_unit_weight', 15, 3);

            $table->string('unit_measurement')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('brutto', 15, 3)->default(0);
            $table->decimal('netto', 15, 3)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total_sum', 15, 2)->default(0);
            $table->decimal('additional_expenses', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);

            $table->timestamps();

            // foreign
            // $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            // product_subcard_id -> product_subcards.id ?
            // $table->foreign('product_subcard_id')->references('id')->on('product_subcards');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_items');
    }
};
