<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackerReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // фасовка отчеты
        Schema::create('packer_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id'); // Reference to products table
            $table->integer('product_subcard_id')->primaryKey();
            $table->string('status');
            $table->string('delivery_address')->nullable();
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
        Schema::dropIfExists('packer_reports');
    }
}
