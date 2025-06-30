<?php

use App\Shopping\Controllers\NewProductToShoppingDayController;
use App\Shopping\Controllers\ShoppingDayController;
use App\Shopping\Controllers\ShoppingDayItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource(
        'users.shopping-days',
        ShoppingDayController::class
    )->parameters([
        'users' => 'owner',
        'shopping-days' => 'shoppingDay',
    ])->only(['index', 'store', 'show', 'edit', 'update', 'destroy'])
        ->shallow();

    Route::post(
        'shopping-days/{shoppingDay}/products',
        NewProductToShoppingDayController::class
    )->name('shopping-days.products.create');

    Route::resource(
        'shopping-days.items',
        ShoppingDayItemController::class
    )->parameters([
        'shopping-days' => 'shoppingDay',
        'items' => 'shoppingDayItem',
    ])->only(['store']);
});
