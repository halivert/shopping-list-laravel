<?php

use App\Models\User;
use App\Shopping\ShoppingDay;
use App\Stores\Store;

/*
 * The sidebar lists the stores of the owner it is showing, like the
 * shopping days: the user's own, or the owner's on a shared page.
 */

test('pages share the user stores for the sidebar', function () {
    $user = User::factory()->create();
    Store::factory()->create(['owner_id' => $user->id, 'name' => '3B']);
    Store::factory()->create(['owner_id' => $user->id, 'name' => 'Aurrera']);
    Store::factory()->create(); // another owner's

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sidebarStores.owner.id', $user->id)
            ->has('sidebarStores.stores', 2)
            ->where('sidebarStores.stores.0.name', '3B')
            ->where('sidebarStores.stores.1.name', 'Aurrera')
        );
});

test('users without stores still get the sidebar group', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('sidebarStores.owner.id', $user->id)
            ->has('sidebarStores.stores', 0)
        );
});

test('a partner viewing an owner shopping day sees the owner stores', function () {
    $store = Store::factory()->create(['name' => 'Aurrera']);
    $partner = User::factory()->create();
    grantProductsAccess($partner, $store->owner);
    Store::factory()->create(['owner_id' => $partner->id, 'name' => 'Propia']);
    $day = ShoppingDay::factory()->create([
        'owner_id' => $store->owner_id,
        'store_id' => $store->id,
    ]);

    $this->actingAs($partner)
        ->get(route('shopping-days.show', $day))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sidebarStores.owner.id', $store->owner_id)
            ->has('sidebarStores.stores', 1)
            ->where('sidebarStores.stores.0.name', 'Aurrera')
        );
});

test('the shared products page shows the owner stores in the sidebar', function () {
    $store = Store::factory()->create(['name' => '3B']);
    $partner = User::factory()->create();
    grantProductsAccess($partner, $store->owner);

    $this->actingAs($partner)
        ->get(route('products-share.show', $partner->sharedBy()->sole()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sidebarStores.owner.id', $store->owner_id)
            ->where('sidebarStores.stores.0.name', '3B')
        );
});
