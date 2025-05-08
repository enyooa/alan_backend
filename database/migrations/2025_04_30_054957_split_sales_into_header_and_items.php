<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        /* 1. header table ------------------------------------------------ */
        Schema::create('sales', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->cascadeOnDelete();
            $t->foreignUuid('client_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $t->foreignUuid('warehouse_id')
            ->nullable()
            ->constrained('warehouses')
            ->nullOnDelete();

            $t->date('sale_date')->nullable();
            $t->decimal('total_sum', 15, 2)->default(0);
            $t->timestamps();
        });

        /* 2. items table -------------------------------------------------- */
        Schema::create('sale_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $t->foreignUuid('product_subcard_id')->constrained('product_sub_cards');
            $t->string('unit_measurement');
            $t->decimal('amount', 15, 3)->default(0);
            $t->decimal('price', 12, 2)->default(0);
            $t->decimal('total_sum', 15, 2)->default(0);
            $t->timestamps();
        });

        /* 3. OPTIONAL: migrate old flat rows ----------------------------- */
        if (Schema::hasTable('sales_flat')) {                // старое имя
            DB::table('sales_flat')->orderBy('id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $saleId = DB::table('sales')->insertGetId([
                        'client_id'    => $row->client_id     ?? null,
                        'warehouse_id' => $row->warehouse_id  ?? null,
                        'sale_date'    => $row->created_at,
                        'total_sum'    => $row->totalsum       ?? ($row->amount * $row->price),
                        'created_at'   => $row->created_at,
                        'updated_at'   => $row->updated_at,
                    ]);

                    DB::table('sale_items')->insert([
                        'sale_id'            => $saleId,
                        'product_subcard_id' => $row->product_subcard_id,
                        'unit_measurement'   => $row->unit_measurement,
                        'amount'             => $row->amount,
                        'price'              => $row->price,
                        'total_sum'          => $row->amount * $row->price,
                        'created_at'         => $row->created_at,
                        'updated_at'         => $row->updated_at,
                    ]);
                }
            });
            // Schema::drop('sales_flat');  // ← раскомментируйте, когда убедитесь
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
