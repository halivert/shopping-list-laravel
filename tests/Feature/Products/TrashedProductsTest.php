<?php

use App\Models\User;
use App\Products\Product;

test('owner can view trashed products page', function () {
    $user = User::factory()->create();
    $deleted = Product::factory(2)->create(['owner_id' => $user->id]);
    $deleted->each(fn (Product $product) => $product->delete());

    $this->actingAs($user)
        ->get(route('users.products.trashed', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductsTrashed')
            ->has('owner')
            ->has('products', 2)
        );
});

test('active products do not appear on the trashed products page', function () {
    $user = User::factory()->create();
    Product::factory(2)->create(['owner_id' => $user->id]);
    $deleted = Product::factory()->create(['owner_id' => $user->id]);
    $deleted->delete();

    $this->actingAs($user)
        ->get(route('users.products.trashed', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductsTrashed')
            ->has('products', 1)
            ->where('products.0.id', $deleted->id)
        );
});

test('unauthenticated user is redirected from trashed products page', function () {
    $user = User::factory()->create();

    $this->get(route('users.products.trashed', ['owner' => $user]))
        ->assertRedirect(route('login'));
});

test('other user cannot view trashed products for another user', function () {
    [$owner, $other] = User::factory(2)->create();

    $this->actingAs($other)
        ->get(route('users.products.trashed', ['owner' => $owner]))
        ->assertNotFound(); // UserPolicy uses denyAsNotFound()
});
