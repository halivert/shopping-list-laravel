<?php

use App\Models\User;
use App\Shopping\Events\ShoppingDayItemUpdated;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Support\Facades\Event;

test('updating an item persists the unit price and quantity', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);
    $item = ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'unit_price' => 1,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('shopping-days.items.update', [
            'shoppingDay' => $shoppingDay,
            'shoppingDayItem' => $item,
        ]), ['unitPrice' => 9.8, 'quantity' => 3])
        ->assertRedirect();

    expect($item->fresh())
        ->unit_price->toBe(9.8)
        ->quantity->toBe(3.0);
});

test('a negative unit price is rejected', function () {
    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);
    $item = ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
        'unit_price' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('shopping-days.items.update', [
            'shoppingDay' => $shoppingDay,
            'shoppingDayItem' => $item,
        ]), ['unitPrice' => -5])
        ->assertInvalid(['unitPrice']);

    expect($item->fresh()->unit_price)->toBe(1.0);
});

test('updating an item broadcasts ShoppingDayItemUpdated only to other sockets', function () {
    Event::fake();

    $user = User::factory()->create();
    $shoppingDay = ShoppingDay::factory()->create(['owner_id' => $user->id]);
    $item = ShoppingDayItem::factory()->create([
        'shopping_day_id' => $shoppingDay->id,
    ]);

    $this->actingAs($user)
        ->withHeaders(['X-Socket-ID' => 'socket-123'])
        ->patch(route('shopping-days.items.update', [
            'shoppingDay' => $shoppingDay,
            'shoppingDayItem' => $item,
        ]), ['unitPrice' => 5]);

    // socket is populated from the X-Socket-ID header only when the event
    // is broadcast via broadcast(...)->toOthers() (PendingBroadcast sets it),
    // proving the sender is excluded from its own broadcast.
    Event::assertDispatched(
        ShoppingDayItemUpdated::class,
        fn ($event) => $event->socket === 'socket-123'
    );
});
