<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderIdToExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->unsignedBigInteger('provider_id')->nullable()->after('amount');
        // $table->foreign('provider_id')->references('id')->on('providers');
    });
}

public function down()
{
    Schema::table('expenses', function (Blueprint $table) {
        // $table->dropForeign(['provider_id']);  // if you used a foreign key
        $table->dropColumn('provider_id');
    });
}

}
