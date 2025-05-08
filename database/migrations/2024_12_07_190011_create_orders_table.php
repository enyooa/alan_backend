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
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            $table->foreignUuid('user_id')
            ->constrained('users')
            ->cascadeOnDelete();
            $table->foreignUuid('status_id')
            ->nullable()
            ->constrained('status_docs')
            ->nullOnDelete();
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
