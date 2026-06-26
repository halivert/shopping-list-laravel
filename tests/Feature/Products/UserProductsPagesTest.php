<?php

use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;

test('owner can view products index page', function () {
    $user = User::factory()->create();
    Product::factory(3)->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('users.products.index', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductsIndex')
            ->has('owner')
            ->has('products', 3)
        );
});

test('products index includes last price for products with purchases', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $day = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day->id,
        'product_id' => $product->id,
        'unit_price' => 9.99,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('users.products.index', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductsIndex')
            ->where('products.0.lastPrice', 9.99)
        );
});

test('owner can view products sort page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.products.sort', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductsSort')
            ->has('owner')
            ->has('products')
        );
});

test('unauthenticated user is redirected from products index', function () {
    $user = User::factory()->create();

    $this->get(route('users.products.index', ['owner' => $user]))
        ->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from products sort', function () {
    $user = User::factory()->create();

    $this->get(route('users.products.sort', ['owner' => $user]))
        ->assertRedirect(route('login'));
});

test('other user cannot view products index for another user', function () {
    [$owner, $other] = User::factory(2)->create();

    $this->actingAs($other)
        ->get(route('users.products.index', ['owner' => $owner]))
        ->assertNotFound(); // UserPolicy uses denyAsNotFound()
});
