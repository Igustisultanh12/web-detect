<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/login', function () {
    return view('app');
})->name('login');

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|horizon).*$');
