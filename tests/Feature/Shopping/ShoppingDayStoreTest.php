<?php

use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Stores\Store;
use Illuminate\Support\Facades\Event;

function startShoppingDay(User $actor, User $owner, array $attrs = [])
{
    return test()->actingAs($actor)->post(
        route('users.shopping-days.store', ['owner' => $owner->id]),
        ['date' => now()->toDateString(), ...$attrs]
    );
}

test('starting a shopping day in a store saves the store', function () {
    $store = Store::factory()->create();

    startShoppingDay($store->owner, $store->owner, ['store_id' => $store->id])
        ->assertRedirect();

    expect(ShoppingDay::query()->sole()->store_id)->toBe($store->id);
});

test('starting a shopping day without store uses the first store', function () {
    $first = Store::factory()->create(['name' => '3B']);
    Store::factory()->create(['owner_id' => $first->owner_id, 'name' => 'Aurrera']);

    startShoppingDay($first->owner, $first->owner)->assertRedirect();

    expect(ShoppingDay::query()->sole()->store_id)->toBe($first->id);
});

test('starting a shopping day without any store creates "Mi tienda"', function () {
    $user = User::factory()->create();

    startShoppingDay($user, $user)->assertRedirect();

    $store = $user->stores()->sole();
    expect($store->name)->toBe('Mi tienda');
    expect(ShoppingDay::query()->sole()->store_id)->toBe($store->id);
});

test('a partner starting a day for an owner without stores creates it for the owner', function () {
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    grantProductsAccess($partner, $owner);

    startShoppingDay($partner, $owner)->assertRedirect();

    expect($owner->stores()->count())->toBe(1);
    expect($partner->stores()->count())->toBe(0);
});

test('items of a store shopping day follow the store order', function () {
    $store = Store::factory()->create();
    $owner = $store->owner;
    $milk = Product::factory()->create([
        'owner_id' => $owner->id, 'name' => 'Leche',
        'is_required' => true, 'required_quantity' => 1, 'shopping_index' => 0,
    ]);
    $eggs = Product::factory()->create([
        'owner_id' => $owner->id, 'name' => 'Huevo',
        'is_required' => true, 'required_quantity' => 1, 'shopping_index' => 1,
    ]);
    $bread = Product::factory()->create([
        'owner_id' => $owner->id, 'name' => 'Pan',
        'is_required' => true, 'required_quantity' => 1, 'shopping_index' => 2,
    ]);
    $store->products()->attach([
        $bread->id => ['position' => 0],
        $milk->id => ['position' => 1],
    ]);

    startShoppingDay($owner, $owner, ['store_id' => $store->id]);

    // Eggs have no position in this store: they go to the end
    expect(
        ShoppingDay::query()->sole()->items->pluck('product_id')->all()
    )->toBe([$bread->id, $milk->id, $eggs->id]);
});

test('a shopping day cannot use a store of another owner', function () {
    $user = User::factory()->create();
    $foreign = Store::factory()->create();

    startShoppingDay($user, $user, ['store_id' => $foreign->id])
        ->assertSessionHasErrors('store_id');

    expect(ShoppingDay::query()->count())->toBe(0);
});

test('partner can start a shopping day in an owner store', function () {
    $store = Store::factory()->create();
    $partner = User::factory()->create();
    grantProductsAccess($partner, $store->owner);

    startShoppingDay($partner, $store->owner, ['store_id' => $store->id])
        ->assertRedirect();

    expect(ShoppingDay::query()->sole())
        ->owner_id->toBe($store->owner_id)
        ->store_id->toBe($store->id);
});

test('a product added to a store shopping day takes its store position', function () {
    Event::fake();

    $store = Store::factory()->create();
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);
    $milk = Product::factory()->create([
        'owner_id' => $store->owner_id, 'name' => 'Leche', 'shopping_index' => 9,
    ]);
    $store->products()->attach($milk->id, ['position' => 3]);

    $this->actingAs($store->owner)
        ->post(route('shopping-days.products.create', $day), ['name' => 'Leche']);

    expect($day->items()->sole()->index)->toBe(3);
});

test('a product without store position is added after the last item', function () {
    Event::fake();

    $store = Store::factory()->create();
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);
    $milk = Product::factory()->create(['owner_id' => $store->owner_id, 'name' => 'Leche']);
    $store->products()->attach($milk->id, ['position' => 4]);

    $this->actingAs($store->owner)
        ->post(route('shopping-days.products.create', $day), ['name' => 'Leche']);
    $this->actingAs($store->owner)
        ->post(route('shopping-days.products.create', $day), ['name' => 'Pan']);

    expect($day->items->pluck('index', 'product.name')->all())
        ->toBe(['Leche' => 4, 'Pan' => 5]);
});

test('products added from the edit page take their store position', function () {
    $store = Store::factory()->create();
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);
    $milk = Product::factory()->create(['owner_id' => $store->owner_id, 'shopping_index' => 8]);
    $store->products()->attach($milk->id, ['position' => 2]);

    $this->actingAs($store->owner)
        ->put(route('shopping-days.update', $day), ['products' => [$milk->id]])
        ->assertRedirect();

    expect($day->items()->sole()->index)->toBe(2);
});

test('shopping day resource includes its store', function () {
    $store = Store::factory()->create(['name' => '3B']);
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);

    $this->actingAs($store->owner)
        ->getJson(route('shopping-days.show', $day))
        ->assertOk()
        ->assertJsonPath('store.id', $store->id)
        ->assertJsonPath('store.name', '3B');
});
