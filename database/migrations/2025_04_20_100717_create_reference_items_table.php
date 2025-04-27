<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferenceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // database/migrations/…_create_reference_items_table.php
Schema::create('reference_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('card_id')->nullable();

    // single, correctly‑spelled column + FK
    $table->foreignId('reference_id')              // NOT 2 lines, no typo
          ->constrained('references')              // → references.id
          ->cascadeOnDelete();

    $table->string('name');
    $table->string('description')->nullable();

    $table->float('value')->nullable();
    $table->string('type')->nullable();
    $table->string('country')->nullable();



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
        Schema::dropIfExists('reference_items');
    }
}
