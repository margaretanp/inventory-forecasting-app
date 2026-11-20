<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'category')) {
                $table->string('category', 100)->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->default(0)->after('lead_time_days');
            }
            if (! Schema::hasColumn('products', 'optimal_alpha')) {
                $table->decimal('optimal_alpha', 4, 2)->default(0.30)->after('purchase_price');
            }
            if (! Schema::hasColumn('products', 'safety_stock')) {
                $table->decimal('safety_stock', 10, 2)->default(0)->after('optimal_alpha');
            }
            if (! Schema::hasColumn('products', 'reorder_point')) {
                $table->decimal('reorder_point', 10, 2)->default(0)->after('safety_stock');
            }
            if (! Schema::hasColumn('products', 'status')) {
                $table->enum('status', ['safe', 'warning', 'critical'])
                    ->default('safe')
                    ->after('reorder_point');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }

            $columns = array_filter([
                'category',
                'purchase_price',
                'optimal_alpha',
                'safety_stock',
                'reorder_point',
                'status',
            ], fn(string $col) => Schema::hasColumn('products', $col));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
