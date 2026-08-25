<?php

namespace App\Support;

use App\Models\User;
use Laravel\Cashier\Cashier;
use Stripe\Exception\InvalidRequestException;

class StripeCatalog
{
    public function currencyForPrice(string $priceId): string
    {
        return Cashier::stripe()->prices->retrieve($priceId)->currency;
    }

    public function expireOpenCheckoutSessions(User $user): void
    {
        if (blank($user->stripe_id)) {
            return;
        }

        $stripe = Cashier::stripe();
        $sessions = $stripe->checkout->sessions->all([
            'customer' => $user->stripe_id,
            'status' => 'open',
            'limit' => 100,
        ]);

        foreach ($sessions->data as $session) {
            try {
                $stripe->checkout->sessions->expire($session->id);
            } catch (InvalidRequestException) {
                continue;
            }
        }
    }
}
