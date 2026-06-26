<?php

use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;

// ── basic store (existing behaviour) ─────────────────────────────────────────

test('owner can create a product', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), ['name' => 'Leche'])
        ->assertRedirect();

    $this->assertDatabaseHas('products', ['name' => 'Leche', 'owner_id' => $user->id]);
});

// ── restore-on-re-add (single name) ──────────────────────────────────────────

test('re-adding a deleted product restores it instead of creating a duplicate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'name' => 'eggs']);
    $product->delete();

    $this->actingAs($user)
        ->post(route('products.store'), ['name' => 'eggs']);

    // Same id, deleted_at cleared, no second row
    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    expect(Product::where('owner_id', $user->id)->count())->toBe(1);
});

test('re-add match is case-insensitive', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'name' => 'eggs']);
    $product->delete();

    $this->actingAs($user)
        ->post(route('products.store'), ['name' => 'Eggs']);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    expect(Product::where('owner_id', $user->id)->count())->toBe(1);
});

test('restored product retains its purchase history on re-add', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'name' => 'eggs']);

    $day = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);
    ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day->id,
        'product_id' => $product->id,
        'unit_price' => 3.50,
        'quantity' => 2,
    ]);

    $product->delete();

    $this->actingAs($user)
        ->post(route('products.store'), ['name' => 'Eggs']);

    // The shopping_day_item still points at the original product id
    $this->assertDatabaseHas('shopping_day_items', ['product_id' => $product->id]);
    expect(Product::where('owner_id', $user->id)->count())->toBe(1);
});

test('a name with no trashed match creates a new product', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), ['name' => 'Butter']);

    expect(Product::where('owner_id', $user->id)->count())->toBe(1);
    $this->assertDatabaseHas('products', ['name' => 'Butter', 'owner_id' => $user->id]);
});

test('a trashed product owned by another user is not restored', function () {
    [$owner, $other] = User::factory(2)->create();
    $product = Product::factory()->create(['owner_id' => $owner->id, 'name' => 'eggs']);
    $product->delete();

    $this->actingAs($other)
        ->post(route('products.store'), ['name' => 'eggs']);

    // owner's product stays trashed
    $this->assertSoftDeleted($product);
    // other user gets a fresh product
    expect(Product::where('owner_id', $other->id)->count())->toBe(1);
    expect(Product::where('owner_id', $other->id)->first()->id)->not->toBe($product->id);
});

// ── restore-on-re-add (batch / markdown paste) ────────────────────────────────

test('batch store restores matching trashed names and creates new ones', function () {
    $user = User::factory()->create();
    $trashed = Product::factory()->create(['owner_id' => $user->id, 'name' => 'eggs']);
    $trashed->delete();

    $this->actingAs($user)
        ->post(route('products.store'), ['products' => ['eggs', 'Butter']]);

    // trashed product is restored
    $this->assertDatabaseHas('products', ['id' => $trashed->id, 'deleted_at' => null]);
    // brand-new product created
    $this->assertDatabaseHas('products', ['name' => 'Butter', 'owner_id' => $user->id]);
    expect(Product::where('owner_id', $user->id)->count())->toBe(2);
});
