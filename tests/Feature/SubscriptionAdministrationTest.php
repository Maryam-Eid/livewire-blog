<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Livewire\Livewire;

test('readers cannot visit the subscriptions dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin-subscriptions.index'))
        ->assertForbidden();
});

test('administrators can view estimated mrr and billing health', function () {
    $admin = staffUser(['manage-subscriptions']);

    $monthly = SubscriptionPlan::factory()->create([
        'key' => 'monthly',
        'name' => 'Monthly',
        'stripe_price_id' => 'price_monthly_test',
        'price_amount' => 500,
        'currency' => 'EGP',
        'billing_interval' => 'month',
        'is_active' => true,
    ]);

    $yearly = SubscriptionPlan::factory()->create([
        'key' => 'yearly',
        'name' => 'Yearly',
        'stripe_price_id' => 'price_yearly_test',
        'price_amount' => 4500,
        'currency' => 'EGP',
        'billing_interval' => 'year',
        'is_active' => true,
    ]);

    $activeMonthly = User::factory()->create(['email' => 'active-monthly@example.com']);
    $activeYearly = User::factory()->create(['email' => 'active-yearly@example.com']);
    $ended = User::factory()->create(['email' => 'ended-member@example.com']);
    $pastDue = User::factory()->create(['email' => 'past-due@example.com']);
    $grace = User::factory()->create(['email' => 'grace-member@example.com']);

    createPremiumSubscription($activeMonthly, ['stripe_price' => $monthly->stripe_price_id]);
    createPremiumSubscription($activeYearly, ['stripe_price' => $yearly->stripe_price_id]);
    createPremiumSubscription($ended, [
        'stripe_price' => $monthly->stripe_price_id,
        'stripe_status' => 'canceled',
        'ends_at' => now()->subDay(),
    ]);
    createPremiumSubscription($pastDue, [
        'stripe_price' => $monthly->stripe_price_id,
        'stripe_status' => 'past_due',
    ]);
    createPremiumSubscription($grace, [
        'stripe_price' => $monthly->stripe_price_id,
        'stripe_status' => 'active',
        'ends_at' => now()->addDays(5),
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::subscriptions.index')
        ->assertOk()
        ->assertSee('Estimated MRR')
        ->assertSee('EGP 875.00')
        ->assertSee('Billing health')
        ->assertSee('Total')
        ->assertSee('New this month')
        ->assertSee('Ended')
        ->assertSee('Ending soon')
        ->assertSee('Past due')
        ->assertSee($activeMonthly->email)
        ->assertSee($ended->email)
        ->assertSee($pastDue->email)
        ->assertSee($grace->email);
});

test('subscription status filters hide members that do not match', function () {
    $admin = staffUser(['manage-subscriptions']);
    $active = User::factory()->create(['email' => 'filter-active@example.com']);
    $ended = User::factory()->create(['email' => 'filter-ended@example.com']);

    createPremiumSubscription($active);
    createPremiumSubscription($ended, [
        'stripe_status' => 'canceled',
        'ends_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::subscriptions.index')
        ->call('filterByStatus', 'active')
        ->assertSet('status', 'active')
        ->assertSee($active->email)
        ->assertDontSee($ended->email)
        ->call('filterByStatus', 'ended')
        ->assertSet('status', 'ended')
        ->assertSee($ended->email)
        ->assertDontSee($active->email);
});

test('administrators can save monthly and yearly plans without calling stripe', function () {
    $admin = staffUser(['manage-subscriptions']);

    $this->actingAs($admin);

    Livewire::test('pages::subscriptions.plans')
        ->set('plans.monthly.name', 'Monthly')
        ->set('plans.monthly.stripe_price_id', 'price_monthly_saved')
        ->set('plans.monthly.price_amount', '500')
        ->set('plans.monthly.currency', 'EGP')
        ->set('plans.monthly.billing_interval', 'month')
        ->set('plans.monthly.is_active', true)
        ->set('plans.yearly.name', 'Yearly')
        ->set('plans.yearly.stripe_price_id', 'price_yearly_saved')
        ->set('plans.yearly.price_amount', '4500')
        ->set('plans.yearly.currency', 'EGP')
        ->set('plans.yearly.billing_interval', 'year')
        ->set('plans.yearly.is_active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Subscription plans updated.');

    $this->assertDatabaseHas('subscription_plans', [
        'key' => 'monthly',
        'stripe_price_id' => 'price_monthly_saved',
        'price_amount' => 500,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('subscription_plans', [
        'key' => 'yearly',
        'stripe_price_id' => 'price_yearly_saved',
        'price_amount' => 4500,
        'is_active' => true,
    ]);
});
