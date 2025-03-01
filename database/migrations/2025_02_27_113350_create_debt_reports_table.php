<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtReportsTable extends Migration
{
    public function up()
    {
        Schema::create('debt_reports', function (Blueprint $table) {
            $table->id();
            $table->string('counterparty_name');
            $table->decimal('start_balance_debt', 12, 2)->default(0);
            $table->decimal('incoming_debt', 12, 2)->default(0);
            $table->decimal('outgoing_debt', 12, 2)->default(0);
            $table->decimal('end_balance_debt', 12, 2)->default(0);
            $table->date('report_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('debt_reports');
    }
}
