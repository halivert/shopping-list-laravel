<?php

use App\Models\User;
use App\Products\Product;
use App\Shopping\Events\ShoppingDayItemCreated;
use App\Shopping\ShoppingDay;
use Illuminate\Support\Facades\Event;

test('adding a new product broadcasts ShoppingDayItemCreated', function () {
    Event::fake();

    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('shopping-days.products.create', $shoppingDay), [
            'name' => 'Leche',
        ])
        ->assertRedirect();

    Event::assertDispatched(ShoppingDayItemCreated::class, function ($event) {
        return $event->shoppingDayItem->product->name === 'Leche';
    });
});

test('adding an existing product does not create a duplicate item', function () {
    Event::fake();

    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('shopping-days.products.create', $shoppingDay), ['name' => 'Pan']);

    $this->actingAs($user)
        ->post(route('shopping-days.products.create', $shoppingDay), ['name' => 'Pan']);

    expect($shoppingDay->fresh()->items)->toHaveCount(1);
});

test('adding a soft-deleted product restores it instead of creating a duplicate', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'name' => 'Leche',
    ]);
    $product->delete();

    $this->actingAs($user)
        ->post(route('shopping-days.products.create', $shoppingDay), ['name' => 'Leche'])
        ->assertRedirect();

    // No duplicate — still exactly one product record for this owner+name
    expect(Product::withTrashed()->where('owner_id', $user->id)->count())->toBe(1);

    // Original product was restored (no longer trashed)
    expect($product->fresh()->trashed())->toBeFalse();

    // The shopping day item points at the original product
    expect($shoppingDay->fresh()->items)->toHaveCount(1)
        ->and($shoppingDay->fresh()->items->first()->product_id)->toBe($product->id);
});

test('unauthenticated user cannot add a product', function () {
    $shoppingDay = ShoppingDay::factory()->create();

    $this->post(route('shopping-days.products.create', $shoppingDay), [
        'name' => 'Leche',
    ])->assertRedirect(route('login'));
});

test('user cannot add a product to another user shopping day', function () {
    [$owner, $other] = User::factory(2)->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($other)
        ->post(route('shopping-days.products.create', $shoppingDay), [
            'name' => 'Leche',
        ])->assertNotFound();
});
