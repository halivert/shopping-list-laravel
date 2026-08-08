<?php

use App\Models\User;
use App\Shopping\ShoppingDay;

test('updating the date persists exactly the calendar day sent, with no off-by-one', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create([
        'owner_id' => $user->id,
        'date' => '2026-01-01',
    ]);

    $this->actingAs($user)
        ->patch(route('shopping-days.update', ['shoppingDay' => $shoppingDay]), [
            'date' => '2026-08-07',
        ])
        ->assertRedirect();

    expect($shoppingDay->fresh()->date->format('Y-m-d'))->toBe('2026-08-07');
});

test('updating the date rejects a full ISO instant', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create([
        'owner_id' => $user->id,
        'date' => '2026-01-01',
    ]);

    $this->actingAs($user)
        ->patch(route('shopping-days.update', ['shoppingDay' => $shoppingDay]), [
            'date' => '2026-08-08T03:36:22.000Z',
        ])
        ->assertInvalid(['date']);

    expect($shoppingDay->fresh()->date->format('Y-m-d'))->toBe('2026-01-01');
});

test('the JSON resource exposes date as a plain Y-m-d string', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create([
        'owner_id' => $user->id,
        'date' => '2026-08-07',
    ]);

    $this->actingAs($user)
        ->getJson(route('shopping-days.show', ['shoppingDay' => $shoppingDay]))
        ->assertJsonPath('date', '2026-08-07');
});

test('starting a shopping day persists exactly the calendar day sent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.shopping-days.store', ['owner' => $user]), [
            'date' => '2026-08-07',
        ])
        ->assertRedirect();

    expect(ShoppingDay::query()->where('owner_id', $user->id)->first()->date->format('Y-m-d'))
        ->toBe('2026-08-07');
});

test('starting a shopping day rejects a full ISO instant', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.shopping-days.store', ['owner' => $user]), [
            'date' => '2026-08-08T03:36:22.000Z',
        ])
        ->assertInvalid(['date']);

    expect(ShoppingDay::query()->where('owner_id', $user->id)->count())->toBe(0);
});
