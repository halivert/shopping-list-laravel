<?php

use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;

test('preserving pending items flags unbought products as required', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'is_required' => false,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'product_id' => $product->id,
        'unit_price' => null,
        'quantity' => 3,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-days.preserve-pending', $shoppingDay))
        ->assertRedirect();

    expect($product->fresh()->is_required)->toBeTrue();
});

test('preserving pending items does not touch already bought items', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'is_required' => false,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'product_id' => $product->id,
        'unit_price' => 12.5,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-days.preserve-pending', $shoppingDay))
        ->assertRedirect();

    expect($product->fresh()->is_required)->toBeFalse();
});

test('preserving pending items uses the item quantity as required_quantity', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'is_required' => false,
        'required_quantity' => 1,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'product_id' => $product->id,
        'unit_price' => null,
        'quantity' => 4,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-days.preserve-pending', $shoppingDay));

    expect($product->fresh()->required_quantity)->toBe(4);
});

test('preserving pending items falls back to quantity 1 when item quantity is null', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'is_required' => false,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'product_id' => $product->id,
        'unit_price' => null,
        'quantity' => null,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-days.preserve-pending', $shoppingDay));

    expect($product->fresh()->required_quantity)->toBe(1);
});

test('preserving pending items on a day with nothing pending flags no products', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'is_required' => false,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'product_id' => $product->id,
        'unit_price' => 9.99,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-days.preserve-pending', $shoppingDay))
        ->assertRedirect();

    expect($user->products()->where('is_required', true)->count())->toBe(0);
});

test('unauthenticated user cannot preserve pending items', function () {
    $shoppingDay = ShoppingDay::factory()->create();

    $this->post(route('shopping-days.preserve-pending', $shoppingDay))
        ->assertRedirect(route('login'));
});

test('user cannot preserve pending items on another user shopping day', function () {
    [$owner, $other] = User::factory(2)->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($other)
        ->post(route('shopping-days.preserve-pending', $shoppingDay))
        ->assertNotFound();
});
