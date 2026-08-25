<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'stripe_price_id' => 'price_'.fake()->unique()->lexify('????????????????'),
            'price_amount' => fake()->randomFloat(2, 5, 500),
            'currency' => 'EGP',
            'billing_interval' => fake()->randomElement(['month', 'year']),
            'is_active' => true,
        ];
    }
}
