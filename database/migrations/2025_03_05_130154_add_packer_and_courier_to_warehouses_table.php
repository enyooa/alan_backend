<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackerAndCourierToWarehousesTable extends Migration
{
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Добавляем packer_id и courier_id
            $table->unsignedBigInteger('packer_id')->nullable()->after('manager_id');
            $table->unsignedBigInteger('courier_id')->nullable()->after('packer_id');

            // Опционально добавляем внешние ключи (FK) 
            // с каскадным удалением или обнулением.
            $table->foreign('packer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('courier_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Удаляем внешние ключи
            $table->dropForeign(['packer_id']);
            $table->dropForeign(['courier_id']);

            // Удаляем столбцы
            $table->dropColumn(['packer_id','courier_id']);
        });
    }
}
