<?php

use App\Http\Controllers\Auth\EmployeeSessionController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DeliveryAreaController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::view('/login', 'admin.login')->name('login');

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

    Route::middleware(['auth', 'employee.active', 'permission:cashier.use'])->group(function (): void {
        Route::post('/cashier/orders/{order}/collect-cash', [CashierController::class, 'markPaid'])->name('cashier.orders.collect-cash');
    });

    Route::middleware(['auth', 'employee.active', 'permission:orders.view'])->group(function (): void {
        Route::get('/orders', OrdersIndex::class)->name('orders.index');
        Route::get('/api/orders', [OrderController::class, 'index'])->name('orders.api.index');
        Route::get('/api/orders/{order}', [OrderController::class, 'show'])->name('orders.api.show');
    });

    Route::middleware(['auth', 'employee.active', 'permission:orders.accept'])->group(function (): void {
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    });

    Route::middleware(['auth', 'employee.active', 'permission:orders.cancel'])->group(function (): void {
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });

    Route::middleware(['auth', 'employee.active', 'permission:roles.manage'])->group(function (): void {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::middleware(['auth', 'employee.active'])->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
        Route::get('/search', AdminSearchController::class)->name('search');
    });

    Route::middleware(['auth', 'employee.active', 'permission:dashboard.view'])->group(function (): void {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
    });

    Route::middleware(['auth', 'employee.active'])->group(function (): void {
        Route::get('/session', [EmployeeSessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [EmployeeSessionController::class, 'destroy'])->name('session.destroy');
    });
});
