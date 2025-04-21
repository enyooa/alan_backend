<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('permission_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);   // один раз на пару
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
    }
};
