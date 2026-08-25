<?php

use App\Models\SubscriptionPlan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.account')] #[Title('Billing settings')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->canSubscribeToPremium(), 403);
    }

    public function with(): array
    {
        $user = auth()->user();
        $subscription = $user->subscription('premium');
        $plan = $subscription === null
            ? null
            : SubscriptionPlan::query()
                ->where('stripe_price_id', $subscription->stripe_price)
                ->value('name') ?? 'Premium';

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'hasPremium' => $user->isPremiumSubscriber(),
        ];
    }
};
?>

<div>
    @include('partials.settings-heading')

    <x-pages::settings.layout heading="Billing" subheading="Manage your Premium membership and payment details">
        @if (session('status'))
            <flux:callout variant="secondary" class="mb-6">
                {{ session('status') }}
            </flux:callout>
        @endif

        <div class="rounded-xl border border-zinc-200 p-5 dark:border-white/10">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">Premium membership</flux:heading>
                    <flux:text class="mt-1">
                        @if ($hasPremium)
                            Your {{ $plan }} membership is active.
                        @else
                            You do not have an active Premium membership.
                        @endif
                    </flux:text>
                </div>

                <flux:badge :color="$hasPremium ? 'green' : 'zinc'">
                    {{ $hasPremium ? 'Active' : 'Inactive' }}
                </flux:badge>
            </div>

            @if ($subscription?->onGracePeriod())
                <flux:callout variant="warning" class="mt-5">
                    Your membership is canceled and remains active until {{ $subscription->ends_at?->format('M j, Y') }}.
                </flux:callout>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($subscription !== null && filled(auth()->user()->stripe_id))
                    <form method="POST" action="{{ route('billing.portal') }}">
                        @csrf
                        <flux:button type="submit" variant="primary">Manage in Stripe</flux:button>
                    </form>
                @else
                    <flux:button :href="route('pricing')" wire:navigate variant="primary">View Premium plans</flux:button>
                @endif
            </div>
        </div>
    </x-pages::settings.layout>
</div>