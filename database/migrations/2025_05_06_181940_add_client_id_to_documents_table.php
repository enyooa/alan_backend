<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdToDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            // ─── новые колонки ─────────────────────────────────────
            $table->uuid('client_id')->nullable()->after('status');
            $table->uuid('to_organization_id')->nullable()->after('client_id');

            // ─── внешние ключи ─────────────────────────────────────
            $table->foreign('client_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();           // при удалении клиента обнуляем

            $table->foreign('to_organization_id')
                  ->references('id')->on('organizations')
                  ->nullOnDelete();           // при удалении контрагента обнуляем
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            // сначала убираем FK, затем колонки
            $table->dropForeign(['client_id']);
            $table->dropForeign(['to_organization_id']);

            $table->dropColumn(['client_id', 'to_organization_id']);
        });
    }
}
