<?php

use App\Products\Product;
use App\Shopping\ShoppingDayItem;

/*
 * Products used to allow g and ml. Prices per g/ml are tiny numbers that lose
 * precision in decimal(9,4), so existing data is converted to kg and L.
 */

function runConvertSmallUnitsMigration(): void
{
    $migration = require database_path(
        'migrations/2026_11_18_220000_convert_small_product_units_to_kg_and_l.php'
    );

    $migration->up();
}

test('products in g or ml are converted to kg or L along with their purchases', function (
    string $from,
    string $to,
) {
    $product = Product::factory()->create(['unit' => $from]);
    $item = ShoppingDayItem::factory()->create([
        'product_id' => $product->id,
        'quantity' => 450,
        'unit_price' => 0.0864,
    ]);

    runConvertSmallUnitsMigration();

    expect($product->fresh()->unit)->toBe($to);
    expect($item->fresh())
        ->quantity->toBe(0.45)
        ->unit_price->toBe(86.4);
})->with([
    'grams' => ['g', 'kg'],
    'millilitres' => ['ml', 'L'],
]);

test('unpriced purchases of small units keep a null price', function () {
    $product = Product::factory()->create(['unit' => 'g']);
    $item = ShoppingDayItem::factory()->create([
        'product_id' => $product->id,
        'quantity' => 78,
        'unit_price' => null,
    ]);

    runConvertSmallUnitsMigration();

    expect($item->fresh())
        ->quantity->toBe(0.078)
        ->unit_price->toBeNull();
});

test('products in kg, L or without a unit are left untouched', function (?string $unit) {
    $product = Product::factory()->create(['unit' => $unit]);
    $item = ShoppingDayItem::factory()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 35.5,
    ]);

    runConvertSmallUnitsMigration();

    expect($product->fresh()->unit)->toBe($unit);
    expect($item->fresh())
        ->quantity->toBe(2.0)
        ->unit_price->toBe(35.5);
})->with(['kg', 'L', null]);

test('soft deleted products in small units are converted too', function () {
    $product = Product::factory()->create(['unit' => 'ml']);
    $product->delete();

    runConvertSmallUnitsMigration();

    expect(Product::withTrashed()->find($product->id)->unit)->toBe('L');
});
