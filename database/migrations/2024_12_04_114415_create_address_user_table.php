<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressUserTable extends Migration
{
    public function up()
    {
        Schema::create('address_user', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

            $table->foreignUuid('address_id')
                    ->constrained('addresses')
                    ->cascadeOnDelete();

      $table->unique(['user_id', 'address_id']);


            $table->timestamps();

       });
    }

    public function down()
    {
        Schema::dropIfExists('address_user');
    }
}


