<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number'); // "7076069831"
            $table->string('code');         // e.g. "1234"
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_verifications');
    }
};
