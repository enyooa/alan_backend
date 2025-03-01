<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthUserIdToGeneralWarehousesAndFinancialOrders extends Migration
{
    public function up()
    {
        // 1) general_warehouses
        Schema::table('general_warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('auth_user_id')
                  ->nullable()
                  ->index()
                  ->after('id');
        });

        // 2) financial_orders
        Schema::table('financial_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('auth_user_id')
                  ->nullable()
                  ->index()
                  ->after('id');
        });

        // 3) documents_requests (DocumentRequest)
        Schema::table('documents_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('auth_user_id')
                  ->nullable()
                  ->index()
                  ->after('id');
        });
    }

    public function down()
    {
        Schema::table('general_warehouses', function (Blueprint $table) {
            $table->dropColumn('auth_user_id');
        });

        Schema::table('financial_orders', function (Blueprint $table) {
            $table->dropColumn('auth_user_id');
        });

        Schema::table('documents_requests', function (Blueprint $table) {
            $table->dropColumn('auth_user_id');
        });
    }
}
