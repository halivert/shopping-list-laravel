<?php

use App\Shopping\Controllers\ShoppingDayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource(
        'users.shopping-days',
        ShoppingDayController::class
    )->parameters([
        'users' => 'owner',
        'shopping-days' => 'shoppingDay',
    ])->scoped([
        'users' => 'id',
        'shoppingDay' => 'date',
    ])->only(['store', 'show', 'update', 'destroy']);
});
