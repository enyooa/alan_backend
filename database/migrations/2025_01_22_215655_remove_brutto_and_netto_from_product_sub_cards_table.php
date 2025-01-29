<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveBruttoAndNettoFromProductSubCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_sub_cards', function (Blueprint $table) {
            $table->dropColumn(['brutto', 'netto']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_sub_cards', function (Blueprint $table) {
            $table->double('brutto')->nullable();
            $table->double('netto')->nullable();
        });
    }
}
