<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('stock_movements', 'source')) {
                $table->enum('source', ['manual', 'kanban_sync', 'csv_import'])
                    ->default('manual')
                    ->after('type');
            }
            if (! Schema::hasColumn('stock_movements', 'notes')) {
                $table->string('notes')->nullable()->after('transaction_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }

            $columns = array_filter(
                ['source', 'notes'],
                fn(string $col) => Schema::hasColumn('stock_movements', $col)
            );

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
