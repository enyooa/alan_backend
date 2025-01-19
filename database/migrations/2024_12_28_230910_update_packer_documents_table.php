<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePackerDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packer_documents', function (Blueprint $table) {
            $table->dropColumn('product_subcard_id');
            $table->dropColumn('delivery_address');        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packer_documents', function (Blueprint $table) {
            $table->integer('product_subcard_id');
            $table->string('delivery_address')->nullable();
        });
    }
}
