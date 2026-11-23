<?php
use Illuminate\Support\Facades\Route;
Route::get('/health', fn () => response()->json(['status'=>'ok','application'=>config('app.name'),'timezone'=>config('app.timezone')]))->name('api.health');