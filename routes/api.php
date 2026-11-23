<?php

use App\Http\Controllers\PrintAgentController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\DeliveryAreaController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('print-agent')->middleware(['throttle:120,1', 'module.enabled:Printing'])->name('print-agent.')->group(function (): void {
    Route::post('/jobs/claim', [PrintAgentController::class, 'claim'])->name('jobs.claim');
    Route::post('/jobs/{jobId}/complete', [PrintAgentController::class, 'complete'])->name('jobs.complete');
    Route::post('/jobs/{jobId}/fail', [PrintAgentController::class, 'fail'])->name('jobs.fail');
});

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'application' => config('app.name'),
    'timezone' => config('app.timezone'),
]))->name('api.health');

Route::get('/storefront/{branchCode}/settings', [SettingsController::class, 'show'])->middleware(['throttle:60,1', 'module.enabled:Storefront'])->name('storefront.settings');
Route::get('/delivery-areas/{branchCode}', [DeliveryAreaController::class, 'index'])->middleware(['throttle:60,1', 'module.enabled:Delivery'])->name('delivery-areas.index');
Route::post('/orders/{branchCode}', [OrderController::class, 'store'])->middleware(['throttle:10,1', 'module.enabled:Orders'])->name('orders.store');
Route::get('/orders/{branchCode}/{orderNumber}', [OrderController::class, 'track'])->middleware(['throttle:30,1', 'module.enabled:Orders'])->name('orders.track');

Route::prefix('catalog/{branchCode}')->middleware(['throttle:60,1', 'module.enabled:Catalog'])->name('catalog.')->group(function (): void {
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('/products', [CatalogController::class, 'products'])->name('products');
    Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');
});
