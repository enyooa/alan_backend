<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackerDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // накладные фасовщика
        Schema::create('packer_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('id_courier');
            $table->string('delivery_address')->nullable();
            $table->integer('product_subcard_id');
            $table->double('amount_of_products')->nullable();

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
        Schema::dropIfExists('packer_documents');
    }
}
