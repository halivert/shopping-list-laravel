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
