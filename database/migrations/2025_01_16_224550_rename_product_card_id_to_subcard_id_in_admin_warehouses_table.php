<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameProductCardIdToSubcardIdInAdminWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_warehouses', function (Blueprint $table) {
            $table->renameColumn('product_card_id', 'product_subcard_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_warehouses', function (Blueprint $table) {
            $table->renameColumn('subcard_id', 'product_card_id');
        });
    }
}
