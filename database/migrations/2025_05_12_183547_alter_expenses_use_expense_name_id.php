<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterExpensesUseExpenseNameId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->uuid('expense_name_id')->nullable()->after('id');
        $table->foreign('expense_name_id')->references('id')->on('expense_names')
              ->cascadeOnUpdate()->nullOnDelete();
    });

    /* ► скрипт переноса старых данных */
    DB::transaction(function () {
        $dup = DB::table('expenses')->select('name')->distinct()->pluck('name');
        foreach ($dup as $n) {
            if ($n === null || $n === '') continue;
            $enameId = (string) Str::uuid();
            DB::table('expense_names')->insert([
                'id'   => $enameId,
                'name' => $n,
            ]);
            DB::table('expenses')->where('name', $n)->update([
                'expense_name_id' => $enameId,
            ]);
        }
    });

    Schema::table('expenses', function (Blueprint $table) {
        $table->dropColumn('name');          // больше не нужен
    });
}
public function down(): void
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->string('name')->nullable();
    });

    DB::table('expenses')->update([
        'name' => DB::raw('(SELECT name FROM expense_names WHERE id = expense_name_id)')
    ]);

    Schema::table('expenses', function (Blueprint $table) {
        $table->dropForeign(['expense_name_id']);
        $table->dropColumn('expense_name_id');
    });
}


}
