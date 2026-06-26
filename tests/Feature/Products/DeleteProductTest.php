<?php

use App\Models\Access;
use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;

// ── destroy ───────────────────────────────────────────────────────────────────

test('owner can soft-delete a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect();

    $this->assertSoftDeleted($product);
});

test('deleted product still has its shopping_day_items', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $day = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);
    $item = ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day->id,
        'product_id' => $product->id,
        'unit_price' => 9.99,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product));

    $this->assertSoftDeleted($product);
    $this->assertDatabaseHas('shopping_day_items', ['id' => $item->id]);
});

test('destroy redirects to the products index for the owner', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('users.products.index', ['owner' => $user->id]));
});

test('destroy flashes the deleted product id and name', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'name' => 'Leche']);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertSessionHas('deletedProduct.id', $product->id)
        ->assertSessionHas('deletedProduct.name', 'Leche');
});

test('deleted product disappears from the products index', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)->delete(route('products.destroy', $product));

    $this->actingAs($user)
        ->get(route('users.products.index', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 0));
});

test('stranger cannot delete another users product', function () {
    [$owner, $stranger] = User::factory(2)->create();
    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->delete(route('products.destroy', $product))
        ->assertNotFound();
});

// ── restore ───────────────────────────────────────────────────────────────────

test('owner can restore a soft-deleted product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);
    $product->delete();

    $this->actingAs($user)
        ->post(route('products.restore', $product->id))
        ->assertRedirect();

    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
});

test('restored product reappears in the products index', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id]);
    $product->delete();

    $this->actingAs($user)->post(route('products.restore', $product->id));

    $this->actingAs($user)
        ->get(route('users.products.index', ['owner' => $user]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 1));
});

test('stranger cannot restore another users product', function () {
    [$owner, $stranger] = User::factory(2)->create();
    $product = Product::factory()->create(['owner_id' => $owner->id]);
    $product->delete();

    $this->actingAs($stranger)
        ->post(route('products.restore', $product->id))
        ->assertNotFound();
});

test('approved partner can restore a product', function () {
    [$owner, $partner] = User::factory(2)->create();
    $access = new Access(['user_email' => $partner->email]);
    $access->user_id = $partner->id;
    $access->approved_at = now();
    $access->accessible()->associate($owner);
    $access->save();

    $product = Product::factory()->create(['owner_id' => $owner->id]);
    $product->delete();

    $this->actingAs($partner)
        ->post(route('products.restore', $product->id))
        ->assertRedirect();

    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
});

// ── shopping_day_items relation survives soft delete ──────────────────────────

test('shopping_day_item product relation resolves with withTrashed after product is deleted', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['owner_id' => $user->id, 'name' => 'Arroz']);

    $day = ShoppingDay::factory()->create(['owner_id' => $user->id, 'date' => '2026-01-01']);
    $item = ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day->id,
        'product_id' => $product->id,
    ]);

    $product->delete();

    $item->refresh();
    // The relation must still resolve (via withTrashed) so past shopping days show the name
    expect($item->product)->not->toBeNull()
        ->and($item->product->name)->toBe('Arroz');
});
