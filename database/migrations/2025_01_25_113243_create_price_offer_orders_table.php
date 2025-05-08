<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceOfferOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('price_offer_orders', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            // client who requested the price offer
            $t->foreignUuid('client_id')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete();           // if client deleted, keep header but set NULL

            // delivery address
            $t->foreignUuid('address_id')
              ->nullable()
              ->constrained('addresses')
              ->nullOnDelete();

            // optional warehouse FK (add only if you really use it)
            // $t->foreignUuid('warehouse_id')
            //   ->nullable()
            //   ->constrained('warehouses')
            //   ->nullOnDelete();

            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->decimal('totalsum', 12, 2)->nullable();

            $t->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_offer_orders');
    }
}
