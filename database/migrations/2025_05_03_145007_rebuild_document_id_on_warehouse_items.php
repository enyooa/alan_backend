<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RebuildDocumentIdOnWarehouseItems extends Migration
{
    public function up()
    {


        Schema::table('warehouse_items', function (Blueprint $table) {
            // 2) add it as a UUID
            $table->uuid('document_id')
                  ->nullable()
                  ->after('warehouse_id');

            // 3) and add the cascading FK
            $table->foreign('document_id')
                  ->references('id')->on('documents')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropColumn('document_id');
        });
    }
}
