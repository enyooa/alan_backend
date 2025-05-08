<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_plan', function (Blueprint $t) {
            // $t->uuid('id')->primary();

            $t->foreignUuid('organization_id')
            ->constrained('organizations')
            ->cascadeOnDelete();

      $t->foreignUuid('plan_id')
            ->constrained('plans')
            ->cascadeOnDelete();

            $t->timestamp('starts_at');
            $t->timestamp('ends_at')->nullable();
            // $t->primary(['organization_id','plan_id','starts_at']);
            $t->timestamps();          // created_at  &  updated_at

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_plan');
    }
}
