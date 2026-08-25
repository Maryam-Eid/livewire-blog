<?php

namespace App\Listeners;

use App\Actions\SyncPremiumSubscriber;
use App\Models\User;
use Laravel\Cashier\Events\WebhookHandled;

class SyncSubscriberAfterStripeWebhook
{
    public function __construct(private SyncPremiumSubscriber $syncPremiumSubscriber) {}

    public function handle(WebhookHandled $event): void
    {
        if (! in_array($event->payload['type'] ?? null, [
            'customer.subscription.created',
            'customer.subscription.updated',
        ], true)) {
            return;
        }

        $user = User::query()
            ->where('stripe_id', $event->payload['data']['object']['customer'] ?? null)
            ->first();

        if ($user !== null) {
            $this->syncPremiumSubscriber->execute($user);
        }
    }
}
