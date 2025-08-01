<?php

use App\Products\Controllers\ProductController;
use App\Products\Controllers\ProductsShareController;
use App\Products\Controllers\UserProductsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('products', ProductController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('products-share', ProductsShareController::class)
        ->only(['create', 'show', 'store', 'update', 'destroy'])
        ->parameters(['products-share' => 'access']);

    Route::resource('users.products', UserProductsController::class)
        ->only(['index', 'store'])
        ->parameters(['users' => 'owner']);
});
