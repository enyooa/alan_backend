<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeExpensesDocumentFkCascade extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // 1) снять старый ключ
            $table->dropForeign('expenses_document_id_foreign');

            // 2) повесить новый с ON DELETE CASCADE
            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->cascadeOnDelete();           // (эквивалент ON DELETE CASCADE)
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // вернуть всё как было
            $table->dropForeign(['document_id']);

            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->restrictOnDelete();          // или ->nullOnDelete() — как было
        });
    }
}
