<?php

use App\Models\SubscriptionPlan;
use App\Models\User;

test('guests cannot start premium checkout', function () {
    $this->post(route('billing.checkout', 'monthly'))
        ->assertRedirect(route('login'));
});

test('staff members cannot start premium checkout', function () {
    $this->actingAs(staffUser())
        ->post(route('billing.checkout', 'monthly'))
        ->assertForbidden();
});

test('unknown plans return not found', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('billing.checkout', 'lifetime'))
        ->assertNotFound();
});

test('checkout is unavailable when stripe is not configured', function () {
    SubscriptionPlan::factory()->create([
        'key' => 'monthly',
        'name' => 'Monthly',
        'stripe_price_id' => 'price_monthly_test',
        'price_amount' => 500,
        'currency' => 'EGP',
        'billing_interval' => 'month',
        'is_active' => true,
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('billing.checkout', 'monthly'))
        ->assertServiceUnavailable();
});

test('active premium members are sent back to billing instead of checkout', function () {
    $user = User::factory()->create();
    createPremiumSubscription($user);

    $this->actingAs($user)
        ->post(route('billing.checkout', 'monthly'))
        ->assertRedirect(route('billing.edit'))
        ->assertSessionHas('status', 'You already have an active Premium subscription.');
});

test('the billing portal requires a stripe customer', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('billing.portal'))
        ->assertRedirect(route('billing.edit'))
        ->assertSessionHas('status', 'Start a Premium subscription before opening the billing portal.');
});

test('staff members cannot open the billing portal', function () {
    $this->actingAs(staffUser())
        ->post(route('billing.portal'))
        ->assertForbidden();
});

test('billing success and cancel pages are available to verified readers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('billing.success'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('billing.cancel'))
        ->assertOk();
});
