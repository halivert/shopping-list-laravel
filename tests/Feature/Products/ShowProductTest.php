<?php

use App\Models\Access;
use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;

test('owner can view product show page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductShow')
            ->has('product')
            ->has('stats')
            ->has('purchases')
        );
});

test('product show page has correct stats for a product with purchases', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'required_quantity' => 2,
    ]);

    // Three purchases spread 20 days apart, qty 4, then 20 days later qty 2
    $day1 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);
    $day2 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-21']);
    $day3 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-02-10']);

    // Use non-round prices so PHP json_encode keeps them as floats, not ints
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day1->id,
        'product_id' => $product->id,
        'unit_price' => 10.50,
        'quantity' => 4,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day2->id,
        'product_id' => $product->id,
        'unit_price' => 12.30,
        'quantity' => 2,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day3->id,
        'product_id' => $product->id,
        'unit_price' => 11.20,
        'quantity' => 3,
    ]);

    $avgPrice = round((10.50 + 12.30 + 11.20) / 3, 4); // 11.3333
    $totalSpent = round(10.50 * 4 + 12.30 * 2 + 11.20 * 3, 4); // 91.2
    $avgQty = round((4 + 2 + 3) / 3, 4); // 3.0 → but expressed as int 3 in JSON

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductShow')
            ->where('stats.timesBought', 3)
            ->where('stats.averagePrice', $avgPrice)
            ->where('stats.minPrice', 10.50)
            ->where('stats.maxPrice', 12.30)
            ->where('stats.totalSpent', $totalSpent)
            // 3 purchases: gaps are 20 days (Jan1→Jan21) and 20 days (Jan21→Feb10)
            // JSON serializes round floats as integers, so use int literals here
            ->where('stats.avgDaysBetween', 20)
            ->where('stats.averageQuantity', 3)
        );
});

test('product show page purchases are sorted date-ascending', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $day1 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-03-01']);
    $day2 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);

    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day1->id,
        'product_id' => $product->id,
        'unit_price' => 5.00,
        'quantity' => 1,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day2->id,
        'product_id' => $product->id,
        'unit_price' => 3.00,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('purchases.0.date', '2026-01-01')
            ->where('purchases.1.date', '2026-03-01')
        );
});

test('product show stats are null when product has never been bought', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.timesBought', 0)
            ->where('stats.averagePrice', null)
            ->where('stats.avgDaysBetween', null)
            ->where('stats.daysPerUnit', null)
            ->where('stats.estimatedDuration', null)
            ->where('stats.totalSpent', 0)
        );
});

test('unpriced items in a shopping day are not counted as purchases', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'owner_id' => $user->id,
        'required_quantity' => 2,
    ]);

    $day1 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);
    $day2 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-21']);
    $day3 = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-02-10']);

    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day1->id,
        'product_id' => $product->id,
        'unit_price' => 10.50,
        'quantity' => 4,
    ]);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day2->id,
        'product_id' => $product->id,
        'unit_price' => 12.50,
        'quantity' => 2,
    ]);
    // Listed on day3 but not bought — no price
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day3->id,
        'product_id' => $product->id,
        'unit_price' => null,
        'quantity' => 3,
    ]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/ProductShow')
            // Only the 2 priced items count as purchases
            ->where('stats.timesBought', 2)
            // Average only over the 2 priced items (qty 4 + 2 = avg 3)
            ->where('stats.averageQuantity', 3)
            // Gap is only between day1 and day2 (20 days); day3 (unpriced) excluded
            ->where('stats.avgDaysBetween', 20)
            // purchases list contains only the priced items
            ->where('purchases.0.date', '2026-01-01')
            ->where('purchases.1.date', '2026-01-21')
            ->count('purchases', 2)
        );
});

test('approved partner can view product show page', function () {
    [$owner, $partner] = User::factory(2)->create();
    $access = new Access(['user_email' => $partner->email]);
    $access->user_id = $partner->id;
    $access->approved_at = now();
    $access->accessible()->associate($owner);
    $access->save();

    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($partner)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('products/ProductShow'));
});

test('stranger cannot view another users product', function () {
    [$owner, $stranger] = User::factory(2)->create();
    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->get(route('products.show', $product))
        ->assertNotFound();
});
