<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderIdToFinancialOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('financial_orders', function (Blueprint $table) {

            /* -----------------------------------------------------------
             | 1.  Drop the existing foreign-keys (they are NOT nullable)
             |     so we can alter the columns. MySQL requires this order.
             | ---------------------------------------------------------*/
            $table->dropForeign(['user_id']);
            //$table->dropForeign(['provider_id']);

            /* -----------------------------------------------------------
             | 2.  Modify both columns to nullable()
             |     (needs doctrine/dbal for MySQL 5.7/8)
             | ---------------------------------------------------------*/
            $table->uuid('user_id')->nullable()->change();
            $table->uuid('provider_id')->nullable();

            /* -----------------------------------------------------------
             | 3.  Re-attach the f-keys with ON DELETE SET NULL behaviour
             | ---------------------------------------------------------*/
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();

        });
    }

    public function down(): void
{
    Schema::table('financial_orders', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropForeign(['provider_id']);

        // оставляем nullable → колонку менять НЕ надо
        // $table->uuid('user_id')->nullable(false)->change();
        // $table->uuid('provider_id')->nullable(false)->change();

        $table->foreign('user_id')
              ->references('id')->on('users')
              ->cascadeOnDelete();

        $table->foreign('provider_id')
              ->references('id')->on('providers')
              ->cascadeOnDelete();
    });
}

}
