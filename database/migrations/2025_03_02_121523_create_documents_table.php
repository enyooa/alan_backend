<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_type_id'); // income, transfer, sale, etc.
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('from_warehouse_id')->nullable();
            $table->unsignedBigInteger('to_warehouse_id')->nullable();
            $table->string('status', 10)->nullable();
            $table->dateTime('document_date');
            $table->text('comments')->nullable();
            $table->timestamps();

            // $table->foreign('provider_id')->references('id')->on('providers');
            // ...
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};
