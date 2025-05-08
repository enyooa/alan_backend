<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeWarehouseDocumentFkCascade extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            // снять старый ключ
            $table->dropForeign('warehouse_items_document_id_foreign');

            // повесить новый с каскадом
            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->cascadeOnDelete();      // UUID-тип работает так же
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->dropForeign(['document_id']);

            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->restrictOnDelete();     // или nullOnDelete(), как было
        });
    }
}
