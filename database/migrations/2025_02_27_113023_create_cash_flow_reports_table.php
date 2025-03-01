<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashFlowReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_flow_reports', function (Blueprint $table) {
            $table->id();
            $table->string('cash_name');
            $table->decimal('start_balance', 12, 2)->default(0);
            $table->decimal('incoming', 12, 2)->default(0);
            $table->decimal('outgoing', 12, 2)->default(0);
            $table->decimal('end_balance', 12, 2)->default(0);
            $table->date('report_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_flow_reports');
    }
}
