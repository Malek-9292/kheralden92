<?php

use App\Http\Controllers\Auth\EmployeeSessionController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DeliveryAreaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/session', [EmployeeSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('session.store');

    Route::middleware(['auth', 'employee.active', 'permission:branches.manage'])->group(function (): void {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::patch('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    });

    Route::middleware(['auth', 'employee.active', 'permission:employees.manage'])->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });

    Route::middleware(['auth', 'employee.active', 'permission:catalog.manage'])->group(function (): void {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    });

    Route::middleware(['auth', 'employee.active', 'permission:delivery.manage'])->group(function (): void {
        Route::get('/delivery-areas', [DeliveryAreaController::class, 'index'])->name('delivery-areas.index');
        Route::post('/delivery-areas', [DeliveryAreaController::class, 'store'])->name('delivery-areas.store');
        Route::patch('/delivery-areas/{deliveryArea}', [DeliveryAreaController::class, 'update'])->name('delivery-areas.update');
    });

    Route::middleware(['auth', 'employee.active', 'permission:roles.manage'])->group(function (): void {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::middleware(['auth', 'employee.active'])->group(function (): void {
        Route::get('/session', [EmployeeSessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [EmployeeSessionController::class, 'destroy'])->name('session.destroy');
    });
});
