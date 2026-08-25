<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'monthly' => ['name' => 'Monthly', 'billing_interval' => 'month', 'price_amount' => 500],
            'yearly' => ['name' => 'Yearly', 'billing_interval' => 'year', 'price_amount' => 4500],
        ] as $key => $plan) {
            SubscriptionPlan::query()->firstOrCreate(
                ['key' => $key],
                [
                    ...$plan,
                    'stripe_price_id' => null,
                    'currency' => strtoupper((string) config('cashier.currency', 'egp')),
                    'is_active' => false,
                ],
            );
        }
    }
}
