<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {

            // ───── rename the old column ─────
            $table->renameColumn('current_accounts', 'account');

            // ───── new columns from the sheet ─────
            $table->string('manager_first_name')->after('address');
            $table->string('manager_last_name')->after('manager_first_name');
            $table->string('manager_phone', 30)->after('manager_last_name');

            /*
             | If you want to **store the manager’s role directly
             | as text**, keep the next line.  If the role should
             | reference the `roles` table, see the comment below.
            */
            $table->string('manager_role')->after('manager_phone');

            /*
            |  ► FOREIGN-KEY option (preferred):
            |  $table->foreignId('manager_role_id')
            |        ->after('manager_phone')
            |        ->constrained('roles')
            |        ->cascadeOnUpdate();
            */
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // roll-back in reverse order
            $table->dropColumn([
                'manager_first_name',
                'manager_last_name',
                'manager_phone',
                'manager_role',
            ]);

            $table->renameColumn('account', 'current_accounts');
        });
    }
};
