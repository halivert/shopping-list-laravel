<?php

use App\Models\User;
use App\Products\Product;
use Illuminate\Support\Facades\Route;

/*
 * Error responses render an Inertia page with a way back home instead of
 * Laravel's bare "404 | Not Found" page.
 */

test('an unknown url renders the error page with a 404 status', function () {
    $this->get('/esta-pagina-no-existe')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
});

test('a missing record renders the error page for a signed in user', function () {
    [$owner, $stranger] = User::factory(2)->create();
    $product = Product::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->get(route('products.show', $product))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
});

test('a forbidden action renders the error page with a 403 status', function () {
    Route::middleware('web')->get('/__error-page-test/403', fn () => abort(403));

    $this->actingAs(User::factory()->create())
        ->get('/__error-page-test/403')
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 403)
        );
});

test('json requests still get a json error', function () {
    $this->getJson('/esta-pagina-no-existe')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
