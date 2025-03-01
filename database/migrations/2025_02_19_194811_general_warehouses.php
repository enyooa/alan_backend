<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GeneralWarehouses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_warehouses', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('product_subcard_id'); // You might want to rename this to product_card_id if needed
            $table->integer('user_id')->nullable();
            $table->integer('address_id')->nullable();

            $table->string('unit_measurement')->nullable(); // Ед. измерения
            $table->double('quantity')->nullable(); // Количество
            $table->integer('price')->nullable(); // Цена
            $table->integer('total_sum')->nullable(); // Итоговая сумма
            // Additional fields to match admin_warehouses
            $table->double('brutto')->nullable(); // Brutto weight
            $table->double('netto')->nullable(); // Netto weight
            $table->double('additional_expenses')->nullable(); // Дополнительные расходы
            $table->double('cost_price')->nullable(); // Себестоимость
            $table->double('total_cost')->nullable(); // Общая себестоимость

            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('general_warehouses');
    }
}
