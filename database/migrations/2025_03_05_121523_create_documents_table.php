<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            $table->foreignUuid('document_type_id')
            ->constrained('document_types')
            ->cascadeOnDelete();
            $table->foreignUuid('provider_id')
            ->nullable()
            ->constrained('providers')
            ->nullOnDelete();

            $table->foreignUuid('from_warehouse_id')
            ->nullable()
            ->constrained('warehouses')
            ->nullOnDelete();
            $table->foreignUuid('to_warehouse_id')
            ->nullable()
            ->constrained('warehouses')
            ->nullOnDelete();
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
