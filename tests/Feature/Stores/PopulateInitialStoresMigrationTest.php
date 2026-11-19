<?php

use App\Models\User;
use App\Products\Product;
use App\Stores\Store;

/*
 * The shopping order used to live in products.shopping_index. Existing users
 * get an initial store whose order is copied from it.
 */

function runPopulateInitialStoresMigration(): void
{
    $migration = require database_path(
        'migrations/2026_11_18_230001_populate_initial_stores.php'
    );

    $migration->up();
}

test('users with products get an initial store ordered by shopping index', function () {
    $user = User::factory()->create();
    $first = Product::factory()->create(['owner_id' => $user->id, 'shopping_index' => 0]);
    $second = Product::factory()->create(['owner_id' => $user->id, 'shopping_index' => 5]);
    $unsorted = Product::factory()->create(['owner_id' => $user->id, 'shopping_index' => null]);

    runPopulateInitialStoresMigration();

    $stores = $user->stores()->get();
    expect($stores)->toHaveCount(1);
    expect($stores->first()->name)->toBe('Mi tienda');
    expect($stores->first()->positions())->toBe([
        $first->id => 0,
        $second->id => 5,
    ]);
});

test('soft deleted products keep their position in the initial store', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'shopping_index' => 2]);
    $product->delete();

    runPopulateInitialStoresMigration();

    expect($user->stores()->first()->positions())->toBe([$product->id => 2]);
});

test('users without products get no store', function () {
    $user = User::factory()->create();

    runPopulateInitialStoresMigration();

    expect($user->stores()->count())->toBe(0);
});

test('users that already have stores are left untouched', function () {
    $store = Store::factory()->create(['name' => '3B']);
    Product::factory()->create(['owner_id' => $store->owner_id, 'shopping_index' => 1]);

    runPopulateInitialStoresMigration();

    expect($store->owner->stores()->pluck('name')->all())->toBe(['3B']);
    expect($store->positions())->toBe([]);
});
