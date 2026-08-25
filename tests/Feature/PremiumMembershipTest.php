<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Livewire\Livewire;

test('the pricing page renders the premium lock badge and formatted plan prices', function () {
    SubscriptionPlan::factory()->create([
        'key' => 'monthly',
        'name' => 'Monthly',
        'price_amount' => 500,
        'currency' => 'EGP',
        'billing_interval' => 'month',
        'is_active' => true,
        'stripe_price_id' => 'price_monthly_test',
    ]);

    SubscriptionPlan::factory()->create([
        'key' => 'yearly',
        'name' => 'Yearly',
        'price_amount' => 4500,
        'currency' => 'EGP',
        'billing_interval' => 'year',
        'is_active' => true,
        'stripe_price_id' => 'price_yearly_test',
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Premium membership')
        ->assertSee('premium-lock', false)
        ->assertDontSee('components.premium-lock')
        ->assertSee('EGP 500.00')
        ->assertSee('EGP 4,500.00');
});

test('guests are prompted to log in before they can subscribe', function () {
    SubscriptionPlan::factory()->create([
        'key' => 'monthly',
        'name' => 'Monthly',
        'price_amount' => 500,
        'currency' => 'EGP',
        'billing_interval' => 'month',
        'is_active' => true,
        'stripe_price_id' => 'price_monthly_test',
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Log in to subscribe');
});

test('staff members see that premium is included with their access', function () {
    $this->actingAs(staffUser());

    Livewire::test('pages::pricing')
        ->assertOk()
        ->assertSee('Included with staff access')
        ->assertDontSee('Log in to subscribe');
});

test('active premium subscribers can manage membership from pricing', function () {
    $user = User::factory()->create();
    createPremiumSubscription($user);

    $this->actingAs($user);

    Livewire::test('pages::pricing')
        ->assertOk()
        ->assertSee('Manage membership')
        ->assertDontSee('Log in to subscribe');
});
