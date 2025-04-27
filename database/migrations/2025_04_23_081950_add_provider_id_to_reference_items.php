<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reference_items', function (Blueprint $table) {
            if (!Schema::hasColumn('reference_items', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')
                      ->nullable()
                      ->after('reference_id')
                      ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reference_items', function (Blueprint $table) {
            if (Schema::hasColumn('reference_items', 'provider_id')) {
                $table->dropColumn('provider_id');
            }
        });
    }
};
