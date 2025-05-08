<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            // Use the same UUID type as users.id and roles.id
            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignUuid('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_user');
    }
}
