<?php

namespace App\Services;

use App\Models\Forecast;
use App\Models\Product;
use App\Models\RestockRecommendation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryForecastService
{
    /**
     * Menjalankan perhitungan SES, MAPE, Safety Stock, dan ROP untuk satu produk.
     *
     * @param Product $product
     * @param float $alpha Konstanta pemulusan (0 < alpha < 1), default 0.3
     * @return array
     */
    public function calculateForProduct(Product $product, float $alpha = 0.3): array
    {
        // 1. Rekap total pemakaian ('out') per bulan secara kronologis
        $monthlyMovements = $product->stockMovements()
            ->where('type', 'out')
            ->select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as period_month"),
                DB::raw('SUM(quantity) as total_out')
            )
            ->groupBy('period_month')
            ->orderBy('period_month', 'asc')
            ->get();

        if ($monthlyMovements->isEmpty()) {
            return [
                'product' => $product,
                'forecasts' => [],
                'recommendation' => null,
                'average_mape' => null,
            ];
        }

        // Hapus hasil forecast lama agar tidak duplikat saat hitung ulang
        Forecast::where('product_id', $product->id)->delete();

        $actuals = [];
        $forecasts = [];
        $mapeList = [];
        $savedForecasts = [];

        foreach ($monthlyMovements as $index => $row) {
            $period = $row->period_month;
            $actual = (float) $row->total_out;
            $actuals[] = $actual;

            // Rumus SES: F_1 = A_1; F_t = alpha * A_{t-1} + (1 - alpha) * F_{t-1}
            if ($index === 0) {
                $forecastValue = $actual;
                $mape = null; // periode pertama inisialisasi, belum ada error prediksi
            } else {
                $prevActual = $actuals[$index - 1];
                $prevForecast = $forecasts[$index - 1];
                $forecastValue = ($alpha * $prevActual) + ((1 - $alpha) * $prevForecast);
                $mape = $actual > 0 ? (abs($actual - $forecastValue) / $actual) * 100 : 0;
                $mapeList[] = $mape;
            }

            $forecasts[] = $forecastValue;

            $savedForecasts[] = Forecast::create([
                'product_id' => $product->id,
                'period_month' => $period,
                'actual_qty' => round($actual, 2),
                'forecast_qty' => round($forecastValue, 2),
                'mape_error' => $mape !== null ? round($mape, 2) : null,
            ]);
        }

        // 2. Hitung peramalan untuk 1 periode bulan berikutnya (next month)
        $lastIndex = count($actuals) - 1;
        $lastPeriod = Carbon::createFromFormat('Y-m', $monthlyMovements[$lastIndex]->period_month);
        $nextPeriodMonth = $lastPeriod->copy()->addMonth()->format('Y-m');

        $nextForecastQty = ($alpha * $actuals[$lastIndex]) + ((1 - $alpha) * $forecasts[$lastIndex]);

        $savedForecasts[] = Forecast::create([
            'product_id' => $product->id,
            'period_month' => $nextPeriodMonth,
            'actual_qty' => null, // belum terjadi
            'forecast_qty' => round($nextForecastQty, 2),
            'mape_error' => null,
        ]);

        // 3. Hitung Safety Stock & Reorder Point (ROP) berbasis kebutuhan harian (30 hari/bulan)
        $dailyUsages = array_map(fn ($m) => $m / 30, $actuals);
        $avgDailyUsage = array_sum($dailyUsages) / count($dailyUsages);
        $maxDailyUsage = max($dailyUsages);
        $leadTime = max(1, (int) $product->lead_time_days);

        // Rumus Safety Stock praktis: (Pemakaian Harian Maksimum - Rata-rata Pemakaian Harian) * Lead Time
        $safetyStock = max(0, ($maxDailyUsage - $avgDailyUsage) * $leadTime);

        // Rumus Reorder Point: (Rata-rata Pemakaian Harian * Lead Time) + Safety Stock
        $reorderPoint = ($avgDailyUsage * $leadTime) + $safetyStock;

        // Target stok untuk menutup kebutuhan bulan depan + safety stock
        $targetStock = $nextForecastQty + $safetyStock;
        $recommendedOrderQty = max(0, $targetStock - $product->current_stock);

        // Tentukan status stok saat ini
        if ($product->current_stock <= $safetyStock) {
            $status = 'critical';
        } elseif ($product->current_stock <= $reorderPoint) {
            $status = 'warning';
        } else {
            $status = 'safe';
        }

        // Simpan rekomendasi restock terbaru
        RestockRecommendation::where('product_id', $product->id)->delete();
        $recommendation = RestockRecommendation::create([
            'product_id' => $product->id,
            'safety_stock' => round($safetyStock, 2),
            'reorder_point' => round($reorderPoint, 2),
            'recommended_order_qty' => round($recommendedOrderQty, 2),
            'status' => $status,
        ]);

        $averageMape = count($mapeList) > 0 ? round(array_sum($mapeList) / count($mapeList), 2) : 0;

        return [
            'product' => $product->fresh(),
            'alpha' => $alpha,
            'average_mape' => $averageMape,
            'next_period' => $nextPeriodMonth,
            'next_forecast_qty' => round($nextForecastQty, 2),
            'forecasts' => $savedForecasts,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Menjalankan perhitungan ulang untuk seluruh produk.
     */
    public function calculateAll(float $alpha = 0.3): array
    {
        $results = [];
        foreach (Product::all() as $product) {
            $results[] = $this->calculateForProduct($product, $alpha);
        }
        return $results;
    }
}   