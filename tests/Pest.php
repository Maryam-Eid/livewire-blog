<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Spatie\Permission\Models\Permission;
use Tests\RefreshInMemoryDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshInMemoryDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshInMemoryDatabase::class)
    ->in('Unit');

/**
 * @param  list<string>  $permissions
 */
function staffUser(array $permissions = ['create-post']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission));
    }

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createPremiumSubscription(User $user, array $overrides = []): Subscription
{
    return $user->subscriptions()->create([
        'type' => 'premium',
        'stripe_id' => 'sub_'.Str::uuid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_monthly_test',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
        ...$overrides,
    ]);
}
