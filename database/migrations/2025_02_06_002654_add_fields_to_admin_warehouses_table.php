<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAdminWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_warehouses', function (Blueprint $table) {
            $table->double('brutto')->nullable(); // Brutto weight
            $table->double('netto')->nullable(); // Netto weight
            $table->double('additional_expenses')->nullable(); // Дополнительные расходы
            $table->double('cost_price')->nullable(); // Себестоимость        
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
            $table->dropColumn('brutto');
            $table->dropColumn('netto');
            $table->dropColumn('additional_expenses');
            $table->dropColumn('cost_price');        });
    }
}
