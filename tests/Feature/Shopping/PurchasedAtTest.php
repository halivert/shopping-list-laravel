<?php

use App\Models\User;
use App\Shopping\Events\ShoppingDayItemUpdated;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Support\Facades\Event;

/*
 * purchased_at records when an item was first priced while shopping: the
 * sequence of purchases is the route through the store.
 */

// Only the broadcast: a bare Event::fake() also disables model hooks
beforeEach(fn () => Event::fake([ShoppingDayItemUpdated::class]));

function unpricedItem(): ShoppingDayItem
{
    $user = User::factory()->create();
    $day = ShoppingDay::factory()->create(['owner_id' => $user->id]);

    return ShoppingDayItem::factory()->create([
        'shopping_day_id' => $day->id,
        'unit_price' => null,
    ]);
}

function priceItem(ShoppingDayItem $item, ?float $price)
{
    return test()->actingAs($item->shoppingDay->owner)->patchJson(
        route('shopping-days.items.update', [
            'shoppingDay' => $item->shopping_day_id,
            'shoppingDayItem' => $item,
        ]),
        ['unitPrice' => $price]
    );
}

test('pricing an item records when it was purchased', function () {
    $item = unpricedItem();
    $this->travelTo('2026-11-18 10:15:30');

    priceItem($item, 25)->assertNoContent();

    expect($item->fresh()->purchased_at->toDateTimeString())
        ->toBe('2026-11-18 10:15:30');
});

test('correcting a price keeps the original purchase time', function () {
    $item = unpricedItem();
    $this->travelTo('2026-11-18 10:15:30');
    priceItem($item, 25);

    $this->travelTo('2026-11-18 10:40:00');
    priceItem($item, 27)->assertNoContent();

    expect($item->fresh()->purchased_at->toDateTimeString())
        ->toBe('2026-11-18 10:15:30');
});

test('removing the price clears the purchase time', function () {
    $item = unpricedItem();
    priceItem($item, 25);

    priceItem($item, null)->assertNoContent();

    expect($item->fresh()->purchased_at)->toBeNull();
});

test('changing only the quantity does not mark an item as purchased', function () {
    $item = unpricedItem();

    $this->actingAs($item->shoppingDay->owner)->patchJson(
        route('shopping-days.items.update', [
            'shoppingDay' => $item->shopping_day_id,
            'shoppingDayItem' => $item,
        ]),
        ['quantity' => 3]
    )->assertNoContent();

    expect($item->fresh()->purchased_at)->toBeNull();
});

test('pricing items from the shopping day update records the purchase time', function () {
    $item = unpricedItem();
    $this->travelTo('2026-11-18 11:00:00');

    $this->actingAs($item->shoppingDay->owner)
        ->putJson(route('shopping-days.update', $item->shopping_day_id), [
            'items' => [['id' => $item->id, 'unitPrice' => 12]],
        ])
        ->assertOk();

    expect($item->fresh()->purchased_at->toDateTimeString())
        ->toBe('2026-11-18 11:00:00');
});

test('a zero price counts as not purchased, like the unchecked item in the list', function () {
    $item = unpricedItem();
    priceItem($item, 0)->assertNoContent();
    expect($item->fresh()->purchased_at)->toBeNull();

    $this->travelTo('2026-11-18 12:00:00');
    priceItem($item, 18)->assertNoContent();
    expect($item->fresh()->purchased_at->toDateTimeString())
        ->toBe('2026-11-18 12:00:00');

    priceItem($item, 0)->assertNoContent();
    expect($item->fresh()->purchased_at)->toBeNull();
});
