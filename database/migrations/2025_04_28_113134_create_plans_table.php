<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            $t->string('slug')->unique();          // client, intermediary …
            $t->string('name');
            $t->unsignedBigInteger('price')->default(0);   // 100_000 etc.
            $t->unsignedInteger('period_days')->default(30);
            $t->unsignedInteger('user_limit')->nullable(); // null = unlimited
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
}
