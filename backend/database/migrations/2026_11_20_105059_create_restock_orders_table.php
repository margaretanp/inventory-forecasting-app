<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('restock_orders')) {
            Schema::create('restock_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->decimal('qty_order', 10, 2);
                $table->enum('status', ['to_order', 'purchased', 'received'])->default('to_order');
                $table->enum('trigger_type', ['auto', 'manual'])->default('manual');
                $table->boolean('is_synced')->default(false);
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_orders');
    }
};
