<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhotoProductToReferenceItems extends Migration
{
    public function up(): void
    {
        Schema::table('reference_items', function (Blueprint $table) {
            /* храним только имя файла; nullable – фото может не быть */
            $table->string('photo')->nullable()
                  ->after('country');   // поставьте после нужной колонки
        });
    }

    public function down(): void
    {
        Schema::table('reference_items', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
}
