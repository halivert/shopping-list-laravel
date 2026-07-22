<?php

use App\Models\User;
use App\Products\Product;

test('guests are redirected to the login page', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertStatus(200);
});

test('dashboard includes only deleted product names', function () {
    $user = User::factory()->create();
    Product::factory()->create(['owner_id' => $user->id, 'name' => 'Arroz']);
    $deleted = Product::factory()->create(['owner_id' => $user->id, 'name' => 'Leche']);
    $deleted->delete();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('deletedProductNames', ['Leche'])
        );
});
