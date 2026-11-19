<?php

use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use App\Stores\Store;

/*
 * The store order learns from the order in which products were bought
 * (priced) in that store. Learning happens when the next shopping day in
 * the store starts; each trip moves the order a bit (0.6 old + 0.4 trip).
 */

/** Store with products sorted manually in the given order. */
function storeWithOrder(string ...$names): array
{
    $store = Store::factory()->create();
    $products = collect($names)->mapWithKeys(fn ($name) => [
        $name => Product::factory()->create([
            'owner_id' => $store->owner_id,
            'name' => $name,
        ]),
    ]);

    $store->sortProducts($products->pluck('id')->all());

    return [$store, $products];
}

/** A finished shopping day in $store where $bought were priced in order. */
function shopTrip(Store $store, array $bought, array $dayAttrs = []): ShoppingDay
{
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
        'date' => '2026-11-01',
        ...$dayAttrs,
    ]);

    foreach (array_values($bought) as $i => $product) {
        ShoppingDayItem::factory()->create([
            'shopping_day_id' => $day->id,
            'product_id' => $product->id,
            'index' => $i,
            'unit_price' => 10,
            'purchased_at' => now()->subHour()->addMinutes($i),
        ]);
    }

    return $day;
}

function startDayIn(Store $store): void
{
    test()->actingAs($store->owner)
        ->post(route('users.shopping-days.store', ['owner' => $store->owner_id]), [
            'date' => now()->toDateString(),
            'store_id' => $store->id,
        ])
        ->assertRedirect();
}

/** @return array<int, string> product names in the store order */
function storeOrder(Store $store): array
{
    $names = Product::query()->pluck('name', 'id');

    return collect($store->positions())->keys()
        ->map(fn ($id) => $names[$id])->all();
}

test('a single trip nudges the order without flipping it', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    shopTrip($store, [$p['B'], $p['A']]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'B', 'C']);
});

test('repeated trips reorder the store', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');

    shopTrip($store, [$p['B'], $p['A']]);
    startDayIn($store);
    shopTrip($store, [$p['B'], $p['A']]);
    startDayIn($store);

    expect(storeOrder($store))->toBe(['B', 'A', 'C']);
});

test('a trip is learned only once', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    shopTrip($store, [$p['B'], $p['A']]);

    startDayIn($store);
    startDayIn($store);
    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'B', 'C']);
});

test('items are ordered by the learned order of the new day', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    shopTrip($store, [$p['C'], $p['B'], $p['A']]);
    shopTrip($store, [$p['C'], $p['B'], $p['A']]);
    $p->each->update(['is_required' => true, 'required_quantity' => 1]);

    startDayIn($store);

    $newDay = ShoppingDay::query()->whereNull('learned_at')->sole();
    expect($newDay->items->pluck('product.name')->all())->toBe(['C', 'B', 'A']);
});

test('a product never sorted takes its place between its neighbours', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    $x = Product::factory()->create(['owner_id' => $store->owner_id, 'name' => 'X']);
    shopTrip($store, [$p['A'], $x, $p['B']]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'X', 'B', 'C']);
});

test('products far apart swap after a few trips; the rest keep their order', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C', 'D', 'E');

    shopTrip($store, [$p['E'], $p['A']]);
    shopTrip($store, [$p['E'], $p['A']]);
    startDayIn($store);
    $order = storeOrder($store);
    expect(array_search('A', $order))->toBeLessThan(array_search('E', $order));

    shopTrip($store, [$p['E'], $p['A']]);
    startDayIn($store);

    $order = storeOrder($store);
    expect(array_values(array_intersect($order, ['B', 'C', 'D'])))
        ->toBe(['B', 'C', 'D']);
    expect(array_search('E', $order))->toBeLessThan(array_search('A', $order));
});

test('trips marked as not learnable are ignored', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    shopTrip($store, [$p['C'], $p['B'], $p['A']], ['learn_order' => false]);
    shopTrip($store, [$p['C'], $p['B'], $p['A']], ['learn_order' => false]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'B', 'C']);
});

test('trips with fewer than two purchases teach nothing', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    shopTrip($store, [$p['C']]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'B', 'C']);
});

test('items without price are not part of the route', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    $day = shopTrip($store, [$p['A'], $p['B']]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day->id,
        'product_id' => $p['C']->id,
        'unit_price' => null,
        'purchased_at' => null,
    ]);
    shopTrip($store, [$p['A'], $p['B']]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'B', 'C']);
});

test('trips in another store do not change this store', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    $other = Store::factory()->create(['owner_id' => $store->owner_id]);
    shopTrip($other, [$p['C'], $p['B'], $p['A']]);
    shopTrip($other, [$p['C'], $p['B'], $p['A']]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['A', 'B', 'C']);
});

test('stores sorted only by position (migrated) learn too', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    // Rows created by the initial store migration have no score
    DB::table('store_product')->where('store_id', $store->id)->update(['score' => null]);
    shopTrip($store, [$p['C'], $p['B'], $p['A']]);
    shopTrip($store, [$p['C'], $p['B'], $p['A']]);

    startDayIn($store);

    expect(storeOrder($store))->toBe(['C', 'B', 'A']);
});

test('sorting by hand restarts learning from the manual order', function () {
    [$store, $p] = storeWithOrder('A', 'B', 'C');
    shopTrip($store, [$p['B'], $p['A']]);
    startDayIn($store); // B is now close to A

    $store->sortProducts([$p['A']->id, $p['C']->id, $p['B']->id]);
    shopTrip($store, [$p['B'], $p['A']]);
    startDayIn($store);

    // After a manual sort B is far from A again: one trip is not enough
    expect(storeOrder($store)[0])->toBe('A');
});

test('learning can be turned off for a shopping day', function () {
    $store = Store::factory()->create();
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);

    $this->actingAs($store->owner)
        ->putJson(route('shopping-days.update', $day), ['learnOrder' => false])
        ->assertOk()
        ->assertJsonPath('learnOrder', false);

    expect($day->fresh()->learn_order)->toBeFalse();
});

test('a learned trip says so and its switch can no longer change', function () {
    [$store, $p] = storeWithOrder('A', 'B');
    $day = shopTrip($store, [$p['B'], $p['A']]);
    startDayIn($store);

    $this->actingAs($store->owner)
        ->getJson(route('shopping-days.show', $day))
        ->assertJsonPath('learned', true);

    $this->actingAs($store->owner)
        ->putJson(route('shopping-days.update', $day), ['learnOrder' => false])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('learnOrder');

    expect($day->fresh()->learn_order)->toBeTrue();
});

test('a trip not learned yet says so', function () {
    [$store, $p] = storeWithOrder('A', 'B');
    $day = shopTrip($store, [$p['B'], $p['A']]);

    $this->actingAs($store->owner)
        ->getJson(route('shopping-days.show', $day))
        ->assertJsonPath('learned', false);
});
