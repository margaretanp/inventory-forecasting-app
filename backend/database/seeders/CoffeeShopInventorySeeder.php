<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CoffeeShopInventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'sku' => 'BN-ESP-001',
                'name' => 'Espresso House Blend Beans',
                'unit' => 'gram',
                'current_stock' => 4500, // sengaja dibuat menipis (critical)
                'lead_time_days' => 3,
                'monthly_usage' => [24000, 25500, 23800, 26200, 27000, 28500, 29000, 27800, 28200, 30100, 31500, 32000],
            ],
            [
                'sku' => 'MLK-UHT-002',
                'name' => 'Fresh Milk Full Cream',
                'unit' => 'liter',
                'current_stock' => 48, // mendekati titik pesan ulang (warning)
                'lead_time_days' => 2,
                'monthly_usage' => [310, 325, 300, 340, 355, 370, 380, 365, 375, 390, 410, 420],
            ],
            [
                'sku' => 'SYR-ARN-003',
                'name' => 'Gula Aren Cair Premium',
                'unit' => 'liter',
                'current_stock' => 65, // aman (safe)
                'lead_time_days' => 2,
                'monthly_usage' => [95, 102, 98, 110, 115, 120, 118, 122, 125, 130, 135, 140],
            ],
            [
                'sku' => 'PWD-MTC-004',
                'name' => 'Bubuk Matcha Pure',
                'unit' => 'gram',
                'current_stock' => 1800, // menipis (critical)
                'lead_time_days' => 4,
                'monthly_usage' => [8500, 9000, 8800, 9500, 10200, 11000, 10800, 11200, 11500, 12000, 12400, 12800],
            ],
            [
                'sku' => 'PKG-CUP-005',
                'name' => 'Plastic Cup 14oz + Lid',
                'unit' => 'pcs',
                'current_stock' => 1400, // aman (safe)
                'lead_time_days' => 3,
                'monthly_usage' => [2100, 2250, 2150, 2300, 2450, 2600, 2650, 2550, 2600, 2750, 2850, 2950],
            ],
        ];

        // Generate data 12 bulan ke belakang dari bulan berjalan (Nov 2026)
        $startMonth = Carbon::create(2025, 12, 1);

        foreach ($items as $item) {
            $product = Product::create([
                'sku' => $item['sku'],
                'name' => $item['name'],
                'unit' => $item['unit'],
                'current_stock' => $item['current_stock'],
                'lead_time_days' => $item['lead_time_days'],
            ]);

            foreach ($item['monthly_usage'] as $index => $totalMonthOut) {
                $monthDate = $startMonth->copy()->addMonths($index);

                // Catat stok masuk (restock awal bulan) sedikit di atas kebutuhan
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => round($totalMonthOut * 1.05, 2),
                    'transaction_date' => $monthDate->copy()->startOfMonth()->format('Y-m-d'),
                ]);

                // Pecah pemakaian bulanan menjadi 4 transaksi mingguan agar realistis
                $weeklyParts = [0.23, 0.25, 0.24, 0.28];
                $days = [5, 12, 19, 26];

                foreach ($weeklyParts as $wIdx => $ratio) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => round($totalMonthOut * $ratio, 2),
                        'transaction_date' => $monthDate->copy()->day($days[$wIdx])->format('Y-m-d'),
                    ]);
                }
            }
        }
    }
}