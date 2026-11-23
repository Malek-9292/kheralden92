<?php

use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\DeliveryAreaController;
use App\Http\Controllers\Storefront\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'application' => config('app.name'),
    'timezone' => config('app.timezone'),
]))->name('api.health');

Route::get('/delivery-areas/{branchCode}', [DeliveryAreaController::class, 'index'])->name('delivery-areas.index');
Route::post('/orders/{branchCode}', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');

Route::prefix('catalog/{branchCode}')->name('catalog.')->group(function (): void {
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('/products', [CatalogController::class, 'products'])->name('products');
    Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');
});
