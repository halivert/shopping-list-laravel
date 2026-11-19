<?php

use App\Stores\Controllers\StoreController;
use App\Stores\Controllers\StoreProductsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('users.stores', StoreController::class)
        ->parameters(['users' => 'owner'])
        ->only(['store', 'update', 'destroy'])
        ->shallow();

    Route::post('stores/{store}/products', [StoreProductsController::class, 'store'])
        ->name('stores.products.store');
});
