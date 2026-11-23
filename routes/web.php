<?php

use App\Http\Controllers\Auth\EmployeeSessionController;
use App\Http\Controllers\Admin\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/session', [EmployeeSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('session.store');

    Route::middleware(['auth', 'employee.active', 'permission:employees.manage'])->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });

    Route::middleware(['auth', 'employee.active'])->group(function (): void {
        Route::get('/session', [EmployeeSessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [EmployeeSessionController::class, 'destroy'])->name('session.destroy');
    });
});
