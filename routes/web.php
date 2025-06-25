<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocalLoginController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    [DashboardController::class, 'index']
)->middleware(['auth'])->name('home');

if (App::isLocal()) {
    Route::post('local-login', LocalLoginController::class)
        ->name('local-login');
}

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/products.php';
require __DIR__ . '/shopping.php';
