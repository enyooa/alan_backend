<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        /********************************************
         * STEP 0: REMOVE ORPHANS IN THESE 5 TABLES
         ********************************************/
        // 1) baskets
        DB::table('baskets')
            ->whereNotIn('product_subcard_id', function ($q) {
                $q->select('id')->from('product_sub_cards');
            })
            ->delete();

        // 2) favorites
        DB::table('favorites')
            ->whereNotIn('product_subcard_id', function ($q) {
                $q->select('id')->from('product_sub_cards');
            })
            ->delete();

        // 3) sales
        DB::table('sales')
            ->whereNotIn('product_subcard_id', function ($q) {
                $q->select('id')->from('product_sub_cards');
            })
            ->delete();

        // 4) admin_warehouses
        DB::table('admin_warehouses')
            ->whereNotIn('product_subcard_id', function ($q) {
                $q->select('id')->from('product_sub_cards');
            })
            ->delete();

        // 5) general_warehouses
        DB::table('general_warehouses')
            ->whereNotIn('product_subcard_id', function ($q) {
                $q->select('id')->from('product_sub_cards');
            })
            ->delete();

        /***********************************************
         * STEP 1: FIX FOREIGN KEYS IN THESE 5 TABLES
         ***********************************************/

        /**
         * BASKETS
         */
        Schema::table('baskets', function (Blueprint $table) {
            // If a foreign key already existed, you might need $table->dropForeign(['product_subcard_id']);
            $table->unsignedBigInteger('product_subcard_id')->change();

            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->cascadeOnDelete();
        });

        /**
         * FAVORITES
         */
        Schema::table('favorites', function (Blueprint $table) {
            // $table->dropForeign(['product_subcard_id']);
            $table->unsignedBigInteger('product_subcard_id')->change();

            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->cascadeOnDelete();
        });

        /**
         * SALES
         */
        Schema::table('sales', function (Blueprint $table) {
            // $table->dropForeign(['product_subcard_id']);
            $table->unsignedBigInteger('product_subcard_id')->change();

            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->cascadeOnDelete();
        });

        /**
         * ADMIN_WAREHOUSES
         */
        Schema::table('admin_warehouses', function (Blueprint $table) {
            // If there's a unique constraint, drop it: $table->dropUnique('unique_inventory');
            // $table->dropForeign(['product_subcard_id']);
            $table->unsignedBigInteger('product_subcard_id')->change();

            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->cascadeOnDelete();
        });

        /**
         * GENERAL_WAREHOUSES
         */
        Schema::table('general_warehouses', function (Blueprint $table) {
            // $table->dropForeign(['product_subcard_id']);
            $table->unsignedBigInteger('product_subcard_id')->change();

            $table->foreign('product_subcard_id')
                  ->references('id')->on('product_sub_cards')
                  ->cascadeOnDelete();
        });
    }

    public function down()
    {
        /***********************************************
         * Revert changes if needed
         ***********************************************/

        /**
         * BASKETS
         */
        Schema::table('baskets', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);
            $table->integer('product_subcard_id')->change();
        });

        /**
         * FAVORITES
         */
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);
            $table->integer('product_subcard_id')->change();
        });

        /**
         * SALES
         */
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);
            $table->integer('product_subcard_id')->change();
        });

        /**
         * ADMIN_WAREHOUSES
         */
        Schema::table('admin_warehouses', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);
            $table->integer('product_subcard_id')->change();

            // If you removed a unique constraint, you could re-add it here:
            // $table->unique('product_subcard_id', 'unique_inventory');
        });

        /**
         * GENERAL_WAREHOUSES
         */
        Schema::table('general_warehouses', function (Blueprint $table) {
            $table->dropForeign(['product_subcard_id']);
            $table->integer('product_subcard_id')->change();
        });
    }
};
