<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkerUserIdToDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Добавляем столбец worker_user_id
            $table->unsignedBigInteger('worker_user_id')->nullable()->after('id');

            // Опционально добавляем внешний ключ
            $table->foreign('worker_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null'); 
                  // или ->nullOnDelete(); 
                  // (выберите нужное вам поведение при удалении пользователя)
        });
    }

    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Сначала удаляем foreign key
            $table->dropForeign(['worker_user_id']);

            // Удаляем столбец
            $table->dropColumn('worker_user_id');
        });
    }
}
