<?php

use App\Models\SubscriptionPlan;

test('subscription plans format prices in the billing currency', function () {
    $plan = new SubscriptionPlan([
        'price_amount' => 4500,
        'currency' => 'egp',
    ]);

    expect($plan->formattedPrice())->toBe('EGP 4,500.00');
});

test('subscription plans without a price ask readers to contact us', function () {
    $plan = new SubscriptionPlan([
        'price_amount' => null,
        'currency' => 'EGP',
    ]);

    expect($plan->formattedPrice())->toBe('Contact us');
});
