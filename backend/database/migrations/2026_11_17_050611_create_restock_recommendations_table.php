<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('safety_stock', 12, 2);
            $table->decimal('reorder_point', 12, 2);
            $table->decimal('recommended_order_qty', 12, 2);
            $table->enum('status', ['safe', 'warning', 'critical'])->default('safe');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_recommendations');
    }
};