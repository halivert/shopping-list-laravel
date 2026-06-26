<?php

use App\Models\Access;
use App\Models\User;
use App\Products\Product;

test('approved partner can view shared products page with checklist fields', function () {
    [$owner, $partner] = User::factory(2)->create();
    $access = new Access(['user_email' => $partner->email]);
    $access->user_id = $partner->id;
    $access->approved_at = now();
    $access->accessible()->associate($owner);
    $access->save();

    Product::factory()->create([
        'owner_id' => $owner->id,
        'is_required' => true,
        'required_quantity' => 2,
    ]);

    $this->actingAs($partner)
        ->get(route('products-share.show', $access))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/SharedProducts')
            ->has('products', 1)
            ->where('products.0.isRequired', true)
            ->where('products.0.requiredQuantity', 2)
        );
});

test('unapproved user cannot view shared products page', function () {
    [$owner, $stranger] = User::factory(2)->create();
    $access = new Access(['user_email' => 'other@example.com']);
    $access->accessible()->associate($owner);
    $access->save();

    $this->actingAs($stranger)
        ->get(route('products-share.show', $access))
        ->assertNotFound();
});
