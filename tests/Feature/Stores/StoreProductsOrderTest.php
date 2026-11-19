<?php

use App\Models\User;
use App\Products\Product;
use App\Stores\Store;

test('saving a store order stores each product position', function () {
    $store = Store::factory()->create();
    [$a, $b, $c] = Product::factory(3)->create(['owner_id' => $store->owner_id]);

    $this->actingAs($store->owner)
        ->postJson(route('stores.products.store', $store), [
            'products' => [$c->id, $a->id, $b->id],
        ])
        ->assertNoContent();

    expect($store->positions())->toBe([
        $c->id => 0,
        $a->id => 1,
        $b->id => 2,
    ]);
});

test('saving a store order again replaces previous positions', function () {
    $store = Store::factory()->create();
    [$a, $b] = Product::factory(2)->create(['owner_id' => $store->owner_id]);
    $store->products()->attach([$a->id => ['position' => 0], $b->id => ['position' => 1]]);

    $this->actingAs($store->owner)
        ->postJson(route('stores.products.store', $store), [
            'products' => [$b->id, $a->id],
        ])
        ->assertNoContent();

    expect($store->positions())->toBe([$b->id => 0, $a->id => 1]);
});

test('store order does not touch other stores or the shopping index', function () {
    $store = Store::factory()->create();
    $other = Store::factory()->create(['owner_id' => $store->owner_id]);
    $product = Product::factory()->create([
        'owner_id' => $store->owner_id,
        'shopping_index' => 7,
    ]);
    $other->products()->attach($product->id, ['position' => 3]);

    $this->actingAs($store->owner)
        ->postJson(route('stores.products.store', $store), [
            'products' => [$product->id],
        ])
        ->assertNoContent();

    expect($other->positions())->toBe([$product->id => 3]);
    expect($product->fresh()->shopping_index)->toBe(7);
});

test('store order rejects products of another owner', function () {
    $store = Store::factory()->create();
    $foreign = Product::factory()->create();

    $this->actingAs($store->owner)
        ->postJson(route('stores.products.store', $store), [
            'products' => [$foreign->id],
        ])
        ->assertUnprocessable();

    expect($store->positions())->toBe([]);
});

test('stranger cannot sort a store', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['owner_id' => $store->owner_id]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('stores.products.store', $store), [
            'products' => [$product->id],
        ])
        ->assertNotFound();
});

test('sort page includes the owner stores with their positions', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);
    $store = Store::factory()->create(['owner_id' => $user->id, 'name' => '3B']);
    $store->products()->attach($product->id, ['position' => 4]);
    Store::factory()->create(); // another owner's store

    $this->actingAs($user)
        ->get(route('users.products.sort', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductsSort')
            ->has('stores', 1)
            ->where('stores.0.id', $store->id)
            ->where('stores.0.name', '3B')
            ->where('stores.0.positions', [$product->id => 4])
        );
});

test('dashboard includes the user stores', function () {
    $user = User::factory()->create();
    Store::factory()->create(['owner_id' => $user->id, 'name' => '3B']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('stores', 1)
            ->where('stores.0.name', '3B')
        );
});

test('shared products page includes the owner stores', function () {
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    grantProductsAccess($partner, $owner);
    Store::factory()->create(['owner_id' => $owner->id, 'name' => 'Aurrera']);

    $access = $partner->sharedBy()->sole();

    $this->actingAs($partner)
        ->get(route('products-share.show', $access))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('stores', 1)
            ->where('stores.0.name', 'Aurrera')
        );
});
