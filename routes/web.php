<?php

use App\Http\Controllers\Auth\EmployeeSessionController;\nuse App\Http\Controllers\Admin\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/session', [EmployeeSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('session.store');

    Route::middleware(['auth', 'permission:employees.manage'])->group(function (): void {\n        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');\n        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');\n        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');\n    });\n\n    Route::middleware('auth')->group(function (): void {
        Route::get('/session', [EmployeeSessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [EmployeeSessionController::class, 'destroy'])->name('session.destroy');
    });
});
