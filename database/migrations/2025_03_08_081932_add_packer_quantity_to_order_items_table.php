<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackerQuantityToOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Add the new 'packer_quantity' column after 'quantity'
            $table->integer('packer_quantity')->nullable()->after('quantity');
            $table->integer('courier_quantity')->nullable()->after('packer_quantity');

        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('packer_quantity');
            $table->dropColumn('courier_quantity');

        });
    }
}
