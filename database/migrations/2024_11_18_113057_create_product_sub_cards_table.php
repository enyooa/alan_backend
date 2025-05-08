<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSubCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sub_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            $table->string('name');

            // 🔽 turns product_card_id into a real FK that cascades
            $table->foreignUuid('product_card_id')
                  ->constrained('product_cards')
                  ->cascadeOnDelete();

            $table->double('brutto')->nullable();
            $table->double('netto')->nullable();
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
        Schema::dropIfExists('product_sub_cards');
    }
}
