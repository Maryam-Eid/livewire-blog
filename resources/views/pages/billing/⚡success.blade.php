<?php

use App\Actions\SyncPremiumSubscriptionFromCheckout;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Premium Membership')] class extends Component
{
    public function mount(SyncPremiumSubscriptionFromCheckout $syncPremiumSubscriptionFromCheckout): void
    {
        $sessionId = request()->string('session_id')->toString();

        if ($sessionId !== '') {
            $syncPremiumSubscriptionFromCheckout->execute(auth()->user(), $sessionId);
        }
    }

    public function with(): array
    {
        auth()->user()?->load('subscriptions');

        return [
            'hasPremium' => auth()->user()->isPremiumSubscriber(),
        ];
    }
};
?>

<div class="mx-auto max-w-2xl px-4 pb-20 text-center sm:px-6" wire:poll.2s>
    <div class="rounded-2xl border border-gray-200 bg-white p-10 shadow-sm">
        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-100 text-2xl font-bold text-emerald-600">✓</div>
        <h1 class="mt-5 text-3xl font-bold text-gray-950">Payment received</h1>

        @if ($hasPremium)
            <p class="mt-4 text-gray-600">Your Premium membership is active. You can now read every Premium post.</p>
            <a href="{{ route('blog.index') }}" wire:navigate class="mt-7 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700">
                Browse articles
            </a>
        @else
            <p class="mt-4 text-gray-600">Stripe is confirming your subscription. This page will update automatically.</p>
            <span class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-indigo-600">
                <span class="size-4 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600"></span>
                Activating membership
            </span>
        @endif
    </div>
</div>
