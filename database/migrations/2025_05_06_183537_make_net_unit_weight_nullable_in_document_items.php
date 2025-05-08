<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            // предположим DECIMAL(12,3); поправьте под свой тип
            $table->decimal('net_unit_weight', 12, 3)
                  ->nullable()
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->decimal('net_unit_weight', 12, 3)
                  ->nullable(false)
                  ->change();
        });
    }
};
