<?php

use App\Products\Controllers\ProductsShareController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('products-share', ProductsShareController::class)
        ->only(['create', 'store', 'update', 'destroy'])
        ->parameters(['products-share' => 'access']);
});
