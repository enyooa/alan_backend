<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleIdToFinancialElementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('financial_elements', function (Blueprint $table) {
            // Add the role_id column (unsignedBigInteger if referencing roles.id)
            $table->foreignUuid('role_id')
            ->nullable()
            ->after('type')
            ->constrained('roles')
            ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financial_elements', function (Blueprint $table) {
            // Drop the foreign key first, then the column
            $table->dropColumn('role_id');
        });
    }
}
