<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAdminIdToUuidInAdminCashes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_cashes', function (Blueprint $table) {
            $table->dropColumn('admin_id');
        });
        Schema::table('admin_cashes', function (Blueprint $table) {
            $table->foreignUuid('admin_id')
                  ->after('organization_id')
                  ->constrained('users')
                  ->nullable()
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_cashes', function (Blueprint $table) {
            Schema::table('admin_cashes', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            });

            Schema::table('admin_cashes', function (Blueprint $table) {
                $table->integer('admin_id')->after('organization_id');
            });        });
    }
}
