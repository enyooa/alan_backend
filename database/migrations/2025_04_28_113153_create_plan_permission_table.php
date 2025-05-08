<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_permission', function (Blueprint $t) {
            $t->foreignUuid('plan_id')->constrained()->cascadeOnDelete();
            $t->foreignUuid('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['plan_id','permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_permission');
    }
}
