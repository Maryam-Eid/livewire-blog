<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Stripe\Checkout\Session;
use Stripe\Subscription as StripeSubscription;

class SyncPremiumSubscriptionFromCheckout
{
    public function __construct(private SyncPremiumSubscriber $syncPremiumSubscriber) {}

    public function execute(User $user, string $sessionId): bool
    {
        if (blank(config('cashier.secret')) || $sessionId === '') {
            return false;
        }

        if ($user->isPremiumSubscriber()) {
            return true;
        }

        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'subscription.items.data.price'],
        ]);

        if ($session->mode !== Session::MODE_SUBSCRIPTION) {
            return false;
        }

        $customerId = is_string($session->customer)
            ? $session->customer
            : $session->customer?->id;

        if ($customerId !== $user->stripe_id) {
            return false;
        }

        $subscription = $session->subscription;

        if (is_string($subscription)) {
            $subscription = Cashier::stripe()->subscriptions->retrieve($subscription, [
                'expand' => ['items.data.price'],
            ]);
        }

        if (! $subscription instanceof StripeSubscription) {
            return false;
        }

        $this->syncSubscription($user, $subscription);
        $this->syncPremiumSubscriber->execute($user);

        return $user->refresh()->isPremiumSubscriber();
    }

    private function syncSubscription(User $user, StripeSubscription $subscription): void
    {
        $trialEndsAt = isset($subscription->trial_end)
            ? Carbon::createFromTimestamp($subscription->trial_end)
            : null;

        $firstItem = $subscription->items->data[0] ?? null;
        $isSinglePrice = count($subscription->items->data) === 1;

        $record = $user->subscriptions()->updateOrCreate(
            ['stripe_id' => $subscription->id],
            [
                'type' => $subscription->metadata['type'] ?? $subscription->metadata['name'] ?? 'premium',
                'stripe_status' => $subscription->status,
                'stripe_price' => $isSinglePrice && $firstItem !== null ? $firstItem->price->id : null,
                'quantity' => $isSinglePrice && $firstItem !== null && isset($firstItem->quantity)
                    ? $firstItem->quantity
                    : null,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => null,
            ],
        );

        foreach ($subscription->items->data as $item) {
            $record->items()->updateOrCreate(
                ['stripe_id' => $item->id],
                [
                    'stripe_product' => $item->price->product,
                    'stripe_price' => $item->price->id,
                    'quantity' => $item->quantity ?? null,
                ],
            );
        }

        if ($user->trial_ends_at !== null) {
            $user->forceFill(['trial_ends_at' => null])->save();
        }
    }
}
