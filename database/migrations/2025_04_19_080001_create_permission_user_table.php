<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_user', function (Blueprint $table) {
            // if you still want a surrogate PK:
            $table->uuid('id')->primary();

            // ↓ use foreignUuid() instead of foreignId()
            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignUuid('permission_id')
                  ->constrained('permissions')
                  ->cascadeOnDelete();

            $table->timestamps();

            // ensure uniqueness
            $table->unique(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
    }
};
