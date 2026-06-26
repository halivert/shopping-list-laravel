<?php

use App\Models\Access;
use App\Models\User;
use App\Products\Events\ProductCreated;
use App\Products\Events\ProductUpdated;
use App\Products\Product;
use Illuminate\Support\Facades\Event;

test('creating a product dispatches ProductCreated', function () {
    Event::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), ['name' => 'Leche'])
        ->assertRedirect();

    Event::assertDispatched(ProductCreated::class, function ($event) {
        return $event->product->name === 'Leche';
    });
});

test('creating a product on behalf of another user dispatches ProductCreated on their channel', function () {
    Event::fake();

    [$owner, $partner] = User::factory(2)->create();
    $access = new Access(['user_email' => $partner->email]);
    $access->user_id = $partner->id;
    $access->approved_at = now();
    $access->accessible()->associate($owner);
    $access->save();

    $this->actingAs($partner)
        ->post(route('products.store'), ['name' => 'Leche', 'user_id' => $owner->id])
        ->assertRedirect();

    Event::assertDispatched(ProductCreated::class, function ($event) use ($owner) {
        return $event->product->resource->owner_id === $owner->id;
    });
});

test('updating a product dispatches ProductUpdated', function () {
    Event::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('products.update', $product), ['is_required' => true])
        ->assertRedirect();

    Event::assertDispatched(ProductUpdated::class, function ($event) use ($product) {
        return $event->product->resource->id === $product->id;
    });
});

test('approved partner can update owner product is_required', function () {
    [$owner, $partner] = User::factory(2)->create();
    $access = new Access(['user_email' => $partner->email]);
    $access->user_id = $partner->id;
    $access->approved_at = now();
    $access->accessible()->associate($owner);
    $access->save();

    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($partner)
        ->put(route('products.update', $product), ['is_required' => true])
        ->assertRedirect();

    expect($product->fresh()->is_required)->toBeTrue();
});

test('unapproved partner cannot update owner product', function () {
    [$owner, $partner] = User::factory(2)->create();
    // Access exists but not approved (no user_id / approved_at)
    $access = new Access(['user_email' => $partner->email]);
    $access->accessible()->associate($owner);
    $access->save();

    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($partner)
        ->put(route('products.update', $product), ['is_required' => true])
        ->assertNotFound();
});
