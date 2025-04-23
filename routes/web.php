<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    [DashboardController::class, 'index']
)->middleware(['auth'])->name('home');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/products.php';
