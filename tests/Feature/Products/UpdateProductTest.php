<?php

use App\Models\User;
use App\Products\Product;

test('user can update product name', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('products.update', $product), ['name' => 'Leche'])
        ->assertRedirect();

    expect($product->fresh()->name)->toBe('Leche');
});

test('user can mark a product as required without sending name', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'is_required' => false]);

    $this->actingAs($user)
        ->put(route('products.update', $product), ['is_required' => true])
        ->assertRedirect();

    expect($product->fresh()->is_required)->toBeTrue();
});

test('user can unmark a product as required', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'is_required' => true]);

    $this->actingAs($user)
        ->put(route('products.update', $product), ['is_required' => false])
        ->assertRedirect();

    expect($product->fresh()->is_required)->toBeFalse();
});

test('user can update required quantity without sending name', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'required_quantity' => 1]);

    $this->actingAs($user)
        ->put(route('products.update', $product), ['required_quantity' => 3])
        ->assertRedirect();

    expect($product->fresh()->required_quantity)->toBe(3);
});

test('required quantity must be at least 1', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('products.update', $product), ['required_quantity' => 0])
        ->assertSessionHasErrors(['required_quantity']);
});

test('another user cannot update a product', function () {
    [$owner, $other] = User::factory(2)->create();
    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($other)
        ->put(route('products.update', $product), ['name' => 'Leche'])
        ->assertNotFound(); // policy uses denyAsNotFound() to avoid leaking resource existence
});
