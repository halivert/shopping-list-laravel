<?php

use App\Models\User;
use App\Shopping\ShoppingDay;
use App\Stores\Store;

test('owner can create a store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.stores.store', ['owner' => $user]), ['name' => '3B'])
        ->assertRedirect();

    expect($user->stores()->pluck('name')->all())->toBe(['3B']);
});

test('creating a store as json returns the store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('users.stores.store', ['owner' => $user]), ['name' => 'Aurrera'])
        ->assertCreated()
        ->assertJsonPath('name', 'Aurrera');
});

test('store name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('users.stores.store', ['owner' => $user]), ['name' => ''])
        ->assertUnprocessable();
});

test('partner with access can create stores for the owner', function () {
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    grantProductsAccess($partner, $owner);

    $this->actingAs($partner)
        ->post(route('users.stores.store', ['owner' => $owner]), ['name' => '3B'])
        ->assertRedirect();

    expect($owner->stores()->count())->toBe(1);
    expect($partner->stores()->count())->toBe(0);
});

test('stranger cannot create stores for another user', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('users.stores.store', ['owner' => $owner]), ['name' => '3B'])
        ->assertNotFound();
});

test('owner can rename a store', function () {
    $store = Store::factory()->create(['name' => 'Mi tienda']);

    $this->actingAs($store->owner)
        ->putJson(route('stores.update', $store), ['name' => '3B'])
        ->assertNoContent();

    expect($store->fresh()->name)->toBe('3B');
});

test('stranger cannot rename a store', function () {
    $store = Store::factory()->create(['name' => 'Mi tienda']);

    $this->actingAs(User::factory()->create())
        ->put(route('stores.update', $store), ['name' => '3B'])
        ->assertNotFound();

    expect($store->fresh()->name)->toBe('Mi tienda');
});

test('deleting a store keeps its shopping days without store', function () {
    $store = Store::factory()->create();
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);

    $this->actingAs($store->owner)
        ->delete(route('stores.destroy', $store))
        ->assertRedirect();

    expect(Store::query()->find($store->id))->toBeNull();
    expect($day->fresh())->not->toBeNull()
        ->store_id->toBeNull();
});

test('stranger cannot delete a store', function () {
    $store = Store::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('stores.destroy', $store))
        ->assertNotFound();

    expect($store->fresh())->not->toBeNull();
});
