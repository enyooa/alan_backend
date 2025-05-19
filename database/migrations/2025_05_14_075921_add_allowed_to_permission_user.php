<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowedToPermissionUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up(): void
    {
        Schema::table('permission_user', function (Blueprint $t) {
            // ① boolean-флаг, по умолчанию «разрешено» (= grant)
            $t->boolean('allowed')
              ->default(true)
              ->after('permission_id');

            // ② гарантируем одну запись (user_id + permission_id)
            //    если у вас уже composite-unique есть — эту строку пропустите
            $t->unique(['user_id','permission_id']);
        });
    }

    public function down(): void
    {
        Schema::table('permission_user', function (Blueprint $t) {
            $t->dropUnique(['user_id','permission_id']);
            $t->dropColumn('allowed');
        });
    }
}
