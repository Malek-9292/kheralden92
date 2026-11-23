<?php

use App\Http\Controllers\Storefront\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'application' => config('app.name'),
    'timezone' => config('app.timezone'),
]))->name('api.health');

Route::prefix('catalog/{branchCode}')->name('catalog.')->group(function (): void {
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('/products', [CatalogController::class, 'products'])->name('products');
});
