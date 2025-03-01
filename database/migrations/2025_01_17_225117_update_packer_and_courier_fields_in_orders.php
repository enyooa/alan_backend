<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePackerAndCourierFieldsInOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add packer_document_id and courier_document_id to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('packer_document_id')->nullable()->after('courier_id');
            $table->unsignedBigInteger('courier_document_id')->nullable()->after('packer_document_id');
        });

        // Remove packer_document_id and courier_document_id from order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['packer_document_id']);
            $table->dropForeign(['courier_document_id']);
            $table->dropColumn(['packer_document_id', 'courier_document_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert changes
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['packer_document_id', 'courier_document_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('packer_document_id')->nullable();
            $table->unsignedBigInteger('courier_document_id')->nullable();

            // If you had foreign keys, add them back here
            $table->foreign('packer_document_id')->references('id')->on('packer_documents')->onDelete('cascade');
            $table->foreign('courier_document_id')->references('id')->on('courier_documents')->onDelete('cascade');
        });
    }
}
