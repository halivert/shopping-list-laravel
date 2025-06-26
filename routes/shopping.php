<?php

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
    ])->only(['store', 'show', 'edit', 'update', 'destroy'])
        ->shallow();

    Route::resource(
        'shopping-days.items',
        ShoppingDayItemController::class
    )->parameters([
        'shopping-days' => 'shoppingDay',
        'items' => 'shoppingDayItem',
    ])->only(['store', 'destroy']);
});
