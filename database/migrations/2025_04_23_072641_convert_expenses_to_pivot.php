<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {

            /* ───── reference_item_id ───── */
            if (!Schema::hasColumn('expenses', 'reference_item_id')) {
                $table->unsignedBigInteger('reference_item_id')->after('id');
                $table->foreign('reference_item_id')
                      ->references('id')->on('reference_items')
                      ->cascadeOnDelete();
            }

            /* ───── удаляем amount ───── */
            if (Schema::hasColumn('expenses', 'amount')) {
                $table->dropColumn('amount');
            }

            /* ───── удаляем name ───── */
            if (Schema::hasColumn('expenses', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {

            /* ───── восстановить reference_item_id FK ───── */
            if (!Schema::hasColumn('expenses', 'reference_item_id')) {
                $table->unsignedBigInteger('reference_item_id')->after('id');
                $table->foreign('reference_item_id')
                      ->references('id')->on('reference_items')
                      ->cascadeOnDelete();
            }

            /* ───── вернуть name ───── */
            if (!Schema::hasColumn('expenses', 'name')) {
                $table->string('name')->nullable();
            }

            /* ───── вернуть amount ───── */
            if (!Schema::hasColumn('expenses', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0);
            }
        });
    }
};
