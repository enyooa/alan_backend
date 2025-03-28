<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderIdToFinancialOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('financial_orders', function (Blueprint $table) {
            // Add the new provider_id column
            $table->unsignedBigInteger('provider_id')->nullable()->after('product_subcard_id');

            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financial_orders', function (Blueprint $table) {
            // Drop the foreign key first if you created one
            // $table->dropForeign(['provider_id']);

            // Then drop the column
            $table->dropColumn('provider_id');
        });
    }
}
