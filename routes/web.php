<?php

use App\Http\Controllers\Auth\EmployeeSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/session', [EmployeeSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('session.store');

    Route::middleware('auth')->group(function (): void {
        Route::get('/session', [EmployeeSessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [EmployeeSessionController::class, 'destroy'])->name('session.destroy');
    });
});
