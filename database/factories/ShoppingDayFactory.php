<?php

namespace Database\Factories;

use App\Models\User;
use App\Shopping\ShoppingDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Shopping\ShoppingDay>
 */
class ShoppingDayFactory extends Factory
{
    protected $model = ShoppingDay::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'date' => fake()->date(),
        ];
    }
}
