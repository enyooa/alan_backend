<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseReportsTable extends Migration
{
    public function up()
    {
        Schema::create('warehouse_reports', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_name');
            $table->decimal('start_balance', 12, 2)->default(0);
            $table->decimal('arrival', 12, 2)->default(0);
            $table->decimal('consumption', 12, 2)->default(0);
            $table->decimal('end_balance', 12, 2)->default(0);
            $table->decimal('total_sum', 12, 2)->default(0);
            $table->date('report_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_reports');
    }
}
