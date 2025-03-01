<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
            // админ поступление товара и склад
        Schema::create('admin_warehouses', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('product_subcard_id');
            $table->string('unit_measurement')->nullable();// ед измерение
            $table->double('quantity')->nullable(); // количества
            $table->integer('price')->nullable();//цена
            $table->integer('total_sum')->nullable();//итог
            $table->date('date')->nullable();
            $table->double('brutto')->nullable(); // Brutto weight
            $table->double('netto')->nullable(); // Netto weight
            $table->double('additional_expenses')->nullable(); // Дополнительные расходы
            $table->double('cost_price')->nullable();
            $table->double('total_cost')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_warehouses');
    }
};
