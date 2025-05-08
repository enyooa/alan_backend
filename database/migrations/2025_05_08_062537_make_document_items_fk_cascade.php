<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeDocumentItemsFkCascade extends Migration
{
    public function up(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            // 1) снять старый FK
            $table->dropForeign('document_items_document_id_foreign');

            // 2) повесить новый с ON DELETE CASCADE
            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->cascadeOnDelete();     // работает и для UUID
        });
    }

    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->dropForeign(['document_id']);

            // вернём «строгий» вариант (RESTRICT) или тот, что был раньше
            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->restrictOnDelete();   // или ->nullOnDelete()
        });
    }
}
