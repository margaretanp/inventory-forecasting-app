<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->string('unit', 30); // contoh: gram, ml, pcs, kg, liter
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->integer('lead_time_days')->default(1); // waktu tunggu pengiriman supplier (hari)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};