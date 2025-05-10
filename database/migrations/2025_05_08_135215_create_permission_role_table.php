<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->uuid('permission_id');
            $table->uuid('role_id');

            $table->primary(['permission_id','role_id']);

            $table->foreign('permission_id')
                  ->references('id')->on('permissions')
                  ->cascadeOnDelete();

            $table->foreign('role_id')
                  ->references('id')->on('roles')
                  ->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
}
