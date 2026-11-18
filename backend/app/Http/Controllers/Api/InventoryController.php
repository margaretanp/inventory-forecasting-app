<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryForecastService $forecastService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $alpha = (float) $request->query('alpha', 0.3);

        $hasRecommendations = Product::whereHas('latestRecommendation')->count() === Product::count();
        if (!$hasRecommendations || $request->boolean('recalculate')) {
            $this->forecastService->calculateAll($alpha);
        }

        $products = Product::with(['latestRecommendation', 'forecasts' => function ($q) {
            $q->orderBy('period_month', 'asc');
        }])->get();

        $summary = [
            'total_products' => $products->count(),
            'critical_count' => $products->filter(fn($p) => $p->latestRecommendation?->status === 'critical')->count(),
            'warning_count' => $products->filter(fn($p) => $p->latestRecommendation?->status === 'warning')->count(),
            'safe_count' => $products->filter(fn($p) => $p->latestRecommendation?->status === 'safe')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'alpha' => $alpha,
            'summary' => $summary,
            'products' => $products,
        ]);
    }

    public function showForecast(Request $request, Product $product): JsonResponse
    {
        $alpha = (float) $request->query('alpha', 0.3);
        $result = $this->forecastService->calculateForProduct($product, $alpha);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function storeMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($validated['type'] === 'out' && $product->current_stock < $validated['quantity']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok saat ini tidak mencukupi untuk pengeluaran tersebut.',
            ], 422);
        }

        $movement = StockMovement::create($validated);

        if ($validated['type'] === 'in') {
            $product->increment('current_stock', $validated['quantity']);
        } else {
            $product->decrement('current_stock', $validated['quantity']);
        }

        $updatedAnalytics = $this->forecastService->calculateForProduct($product->fresh());

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi stok berhasil dicatat.',
            'movement' => $movement,
            'analytics' => $updatedAnalytics,
        ], 201);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'replace_existing' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Baca baris header & deteksi otomatis pemisah koma (,) atau titik koma (;)
        $firstLine = fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'File CSV kosong atau tidak valid.'], 422);
        }

        // Bersihkan karakter BOM jika ada & samakan huruf kecil
        $header = array_map(fn($col) => trim(preg_replace('/^\xEF\xBB\xBF/', '', strtolower($col))), $header);

        $requiredColumns = ['sku', 'name', 'unit', 'period_month', 'qty_out', 'current_stock', 'lead_time_days'];
        $missing = array_diff($requiredColumns, $header);
        if (!empty($missing)) {
            fclose($handle);
            return response()->json([
                'message' => 'Kolom CSV tidak sesuai format: ' . implode(', ', $missing),
            ], 422);
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) < count($header)) {
                continue;
            }
            $rows[] = array_combine($header, array_slice($data, 0, count($header)));
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json(['message' => 'Tidak ada baris data yang terbaca.'], 422);
        }

        // Hapus data lama di luar DB::transaction agar tidak bentrok dengan implicit commit MySQL
        if ($request->boolean('replace_existing', true)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            \App\Models\RestockRecommendation::truncate();
            \App\Models\Forecast::truncate();
            \App\Models\StockMovement::truncate();
            Product::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        DB::transaction(function () use ($rows) {
            $affectedProducts = [];

            foreach ($rows as $row) {
                $sku = trim($row['sku']);
                if ($sku === '') {
                    continue;
                }

                // 1. Simpan atau update master produk
                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => trim($row['name']),
                        'unit' => trim($row['unit']) ?: 'pcs',
                        'current_stock' => (float) $row['current_stock'],
                        'lead_time_days' => (int) $row['lead_time_days'],
                    ]
                );

                // 2. Simpan riwayat pemakaian bulanan
                $periodMonth = trim($row['period_month']);
                $transactionDate = $periodMonth . '-15';

                StockMovement::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'type' => 'out',
                        'transaction_date' => $transactionDate,
                    ],
                    [
                        'quantity' => (float) $row['qty_out'],
                    ]
                );

                $affectedProducts[$product->id] = $product;
            }

            // 3. Jalankan kalkulasi SES, MAPE, Safety Stock & ROP menggunakan method yang benar
            foreach ($affectedProducts as $product) {
                $this->forecastService->calculateForProduct($product->fresh());
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Import CSV berhasil & peramalan stok telah diperbarui.',
            'total_rows_imported' => count($rows),
            'total_products' => Product::count(),
        ]);
    }
}
