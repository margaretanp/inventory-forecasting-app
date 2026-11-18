<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('period_month', 7);
            $table->decimal('actual_qty', 12, 2)->nullable();
            $table->decimal('forecast_qty', 12, 2);
            $table->decimal('mape_error', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};