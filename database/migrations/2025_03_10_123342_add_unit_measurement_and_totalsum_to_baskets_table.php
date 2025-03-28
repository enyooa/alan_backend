<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitMeasurementAndTotalsumToBasketsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('baskets', function (Blueprint $table) {
            // 'unit_measurement': a string (e.g. "кг" or "штук") - can be nullable
            $table->string('unit_measurement')->nullable()->after('price');

            // 'totalsum': store line-total, e.g. 'quantity * price'
            // Adjust precision if needed, e.g. (10,2) or (15,2)
            $table->decimal('totalsum', 15, 2)->default(0)->after('unit_measurement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('baskets', function (Blueprint $table) {
            $table->dropColumn('unit_measurement');
            $table->dropColumn('totalsum');
        });
    }
}
