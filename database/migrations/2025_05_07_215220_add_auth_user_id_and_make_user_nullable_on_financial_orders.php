<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthUserIdAndMakeUserNullableOnFinancialOrders extends Migration
{
    public function up(): void
    {
        Schema::table('financial_orders', function (Blueprint $table) {

            // avoid duplicate-column error if you accidentally rerun
            if (!Schema::hasColumn('financial_orders', 'auth_user_id')) {
                $table->foreignUuid('auth_user_id')
                      ->nullable()
                      ->after('user_id')   // put right next to user_id
                      ->constrained('users')
                      ->nullOnDelete();    // set NULL if the user disappears
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_orders', function (Blueprint $table) {
            if (Schema::hasColumn('financial_orders', 'auth_user_id')) {
                $table->dropForeign(['auth_user_id']);
                $table->dropColumn('auth_user_id');
            }
        });
    }
}
