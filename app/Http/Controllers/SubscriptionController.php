<?php

namespace App\Http\Controllers;

use App\Actions\StartPremiumCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Checkout;

class SubscriptionController extends Controller
{
    public function checkout(
        Request $request,
        string $plan,
        StartPremiumCheckout $startPremiumCheckout,
    ): Checkout|RedirectResponse {
        return $startPremiumCheckout->execute($request->user(), $plan);
    }

    public function portal(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canSubscribeToPremium(), 403);

        if (blank($request->user()->stripe_id)) {
            return redirect()
                ->route('billing.edit')
                ->with('status', 'Start a Premium subscription before opening the billing portal.');
        }

        return $request->user()->redirectToBillingPortal(route('billing.edit'));
    }
}
