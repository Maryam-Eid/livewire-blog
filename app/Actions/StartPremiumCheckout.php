<?php

namespace App\Actions;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\StripeCatalog;
use Illuminate\Http\RedirectResponse;
use Laravel\Cashier\Checkout;
use Stripe\Exception\InvalidRequestException;

class StartPremiumCheckout
{
    public function __construct(
        private SyncPremiumSubscriber $syncPremiumSubscriber,
        private StripeCatalog $stripeCatalog,
    ) {}

    public function execute(User $user, string $plan): Checkout|RedirectResponse
    {
        abort_unless(in_array($plan, ['monthly', 'yearly'], true), 404);
        abort_unless($user->canSubscribeToPremium(), 403);

        if ($user->subscribed('premium')) {
            return redirect()
                ->route('billing.edit')
                ->with('status', 'You already have an active Premium subscription.');
        }

        $subscriptionPlan = SubscriptionPlan::query()
            ->available()
            ->where('key', $plan)
            ->first();

        abort_if(
            blank(config('cashier.secret')) || $subscriptionPlan === null,
            503,
            'Stripe billing is not configured yet.',
        );

        $this->syncPremiumSubscriber->execute($user);
        $this->stripeCatalog->expireOpenCheckoutSessions($user);

        $priceCurrency = strtolower($this->stripeCatalog->currencyForPrice($subscriptionPlan->stripe_price_id));
        $billingCurrency = strtolower((string) config('cashier.currency'));

        if ($priceCurrency !== $billingCurrency) {
            return redirect()
                ->route('pricing')
                ->with('error', 'This plan uses a different currency than billing ('.strtoupper($billingCurrency).'). Update the Stripe Price ID to an '.strtoupper($billingCurrency).' price.');
        }

        try {
            return $user
                ->newSubscription('premium', $subscriptionPlan->stripe_price_id)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.cancel'),
                ]);
        } catch (InvalidRequestException $exception) {
            report($exception);

            return redirect()
                ->route('pricing')
                ->with('error', 'Stripe could not start checkout for this plan. Open sessions or previous items must use the same currency ('.strtoupper($billingCurrency).').');
        }
    }
}
