<?php

namespace Database\Factories;

use App\Models\Product;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Shopping\ShoppingDayItem>
 */
class ShoppingDayItemFactory extends Factory
{
    protected $model = ShoppingDayItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopping_day_id' => ShoppingDay::factory(),
            'product_id' => Product::factory(),
            'index' => fake()->numberBetween(0, 100),
            'unit_price' => fake()->numberBetween(0, 999999) / 100,
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
