<?php

use App\Http\Controllers\Api\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->group(function () {
    Route::get('/dashboard', [InventoryController::class, 'dashboard']);
    Route::get('/products/{product}/forecast', [InventoryController::class, 'showForecast']);
    Route::post('/movements', [InventoryController::class, 'storeMovement']);
    Route::post('/import', [InventoryController::class, 'importCsv']);
});