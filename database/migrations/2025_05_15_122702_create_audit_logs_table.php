<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable();
            $table->string('event', 50);               // created, updated, deleted
            $table->string('auditable_type');          // полный класс модели
            $table->uuid('auditable_id')->index();     // её ID
            $table->json('old_values')->nullable();    // до изменения
            $table->json('new_values')->nullable();    // после
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
