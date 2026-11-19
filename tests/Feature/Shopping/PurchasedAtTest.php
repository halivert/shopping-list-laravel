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

/*
 * With poor signal in the store a price can reach the server much later
 * (failed saves are retried on the next edit or when leaving the page), so
 * the phone sends when the price was entered.
 */

function priceItemAt(ShoppingDayItem $item, ?float $price, string $pricedAt)
{
    return test()->actingAs($item->shoppingDay->owner)->patchJson(
        route('shopping-days.items.update', [
            'shoppingDay' => $item->shopping_day_id,
            'shoppingDayItem' => $item,
        ]),
        ['unitPrice' => $price, 'pricedAt' => $pricedAt]
    );
}

test('the purchase time comes from the phone when it is sent', function () {
    $item = unpricedItem();
    $this->travelTo('2026-11-18 10:30:00');

    priceItemAt($item, 25, '2026-11-18T10:12:05Z')->assertNoContent();

    expect($item->fresh()->purchased_at->utc()->toDateTimeString())
        ->toBe('2026-11-18 10:12:05');
});

test('a later phone time does not replace the first purchase time', function () {
    $item = unpricedItem();
    priceItemAt($item, 25, '2026-11-18T10:12:05Z');

    priceItemAt($item, 27, '2026-11-18T10:20:00Z')->assertNoContent();

    expect($item->fresh()->purchased_at->utc()->toDateTimeString())
        ->toBe('2026-11-18 10:12:05');
});

test('a phone time without a price does not mark the item as purchased', function () {
    $item = unpricedItem();

    priceItemAt($item, 0, '2026-11-18T10:12:05Z')->assertNoContent();

    expect($item->fresh()->purchased_at)->toBeNull();
});

test('a phone time in the future is rejected', function () {
    $item = unpricedItem();
    $this->travelTo('2026-11-18 10:00:00');

    priceItemAt($item, 25, '2026-11-18T11:00:00Z')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pricedAt');
});
