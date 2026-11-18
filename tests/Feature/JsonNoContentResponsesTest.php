<?php

use App\Models\Access;
use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;

/*
 * Endpoints that redirect back for Inertia visits must answer JSON requests
 * (e.g. from useHttp) with an empty 204 response.
 */

test('updating a product as json responds with no content', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('products.update', $product), ['is_required' => true])
        ->assertNoContent();

    expect($product->fresh()->is_required)->toBeTrue();
});

test('storing a product as json responds with no content', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('products.store'), ['name' => 'Pan'])
        ->assertNoContent();
});

test('deleting a product as json responds with no content', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson(route('products.destroy', $product))
        ->assertNoContent();
});

test('restoring a product as json responds with no content', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);
    $product->delete();

    $this->actingAs($user)
        ->postJson(route('products.restore', $product))
        ->assertNoContent();
});

test('sorting products as json responds with no content', function () {
    $user = User::factory()->create();
    $products = Product::factory()->count(2)->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('users.products.store', ['owner' => $user]), [
            'products' => $products->pluck('id')->reverse()->values()->all(),
            'list' => 'search_index',
        ])
        ->assertNoContent();
});

test('deleting a shopping day as json responds with no content', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson(route('shopping-days.destroy', $shoppingDay))
        ->assertNoContent();
});

test('sharing products as json responds with no content', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('products-share.store'), ['email' => 'friend@example.com'])
        ->assertNoContent();
});

test('accepting and removing a share as json responds with no content', function () {
    $owner = User::factory()->create();
    $friend = User::factory()->create();

    $access = new Access(['user_email' => $friend->email]);
    $access->accessible()->associate($owner);
    $access->save();

    $this->actingAs($friend)
        ->putJson(route('products-share.update', $access))
        ->assertNoContent();

    $this->actingAs($friend)
        ->deleteJson(route('products-share.destroy', $access))
        ->assertNoContent();
});
