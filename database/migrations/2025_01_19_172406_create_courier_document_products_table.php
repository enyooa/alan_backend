<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourierDocumentProductsTable extends Migration
{
    public function up()
    {
        Schema::create('courier_document_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_subcard_id')->constrained('product_sub_cards')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('courier_document_products');
    }
}
