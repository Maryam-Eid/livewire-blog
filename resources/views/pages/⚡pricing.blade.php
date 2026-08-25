<?php

use App\Models\SubscriptionPlan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Premium Membership')] class extends Component
{
    public function with(): array
    {
        return [
            'plans' => SubscriptionPlan::query()->get()->keyBy('key'),
            'isPremium' => auth()->user()?->isPremiumSubscriber() ?? false,
            'canSubscribe' => auth()->user()?->canSubscribeToPremium() ?? true,
        ];
    }
};
?>

<div class="mx-auto max-w-6xl px-4 pb-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl text-center">
        <span class="premium-badge inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 px-3 py-1 text-sm font-semibold text-amber-950 shadow-sm shadow-amber-500/40">
            <x-premium-lock />
            Premium membership
        </span>
        <h1 class="mt-5 text-4xl font-bold tracking-tight text-gray-950 sm:text-5xl">Read more. Receive more.</h1>
        <p class="mt-5 text-lg leading-8 text-gray-600">
            Unlock every Premium article and receive newsletters created exclusively for members.
        </p>
    </div>

    @if (session('error'))
        <div class="mx-auto mt-8 max-w-3xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mx-auto mt-12 grid max-w-4xl gap-6 md:grid-cols-2">
        @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $plan => $label)
            @php($subscriptionPlan = $plans->get($plan))
            <section class="relative rounded-2xl border {{ $plan === 'yearly' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200' }} bg-white p-8 shadow-sm">
                @if ($plan === 'yearly')
                    <span class="absolute -top-3 right-6 rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold text-white">Best value</span>
                @endif

                <h2 class="text-xl font-semibold text-gray-950">{{ $label }}</h2>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-950">{{ $subscriptionPlan?->formattedPrice() ?? 'Not configured' }}</p>
                <p class="mt-1 text-sm text-gray-500">Billed {{ $plan }}</p>

                <ul class="mt-7 space-y-3 text-sm text-gray-700">
                    <li class="flex gap-3"><span class="font-bold text-emerald-600">✓</span> Every Premium article</li>
                    <li class="flex gap-3"><span class="font-bold text-emerald-600">✓</span> Premium member newsletters</li>
                    <li class="flex gap-3"><span class="font-bold text-emerald-600">✓</span> Cancel anytime through Stripe</li>
                </ul>

                <div class="mt-8">
                    @if ($isPremium)
                        <a href="{{ route('billing.edit') }}" wire:navigate class="inline-flex w-full justify-center rounded-lg bg-gray-900 px-4 py-3 font-semibold text-white hover:bg-gray-700">
                            Manage membership
                        </a>
                    @elseif (! $canSubscribe)
                        <div class="w-full rounded-lg bg-emerald-50 px-4 py-3 text-center font-semibold text-emerald-700">
                            Included with staff access
                        </div>
                    @elseif (! auth()->check())
                        <a href="{{ route('login') }}" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                            Log in to subscribe
                        </a>
                    @elseif (! ($subscriptionPlan?->isConfigured() ?? false))
                        <button type="button" disabled class="w-full cursor-not-allowed rounded-lg bg-gray-200 px-4 py-3 font-semibold text-gray-500">
                            Plan not configured yet
                        </button>
                    @else
                        <form method="POST" action="{{ route('billing.checkout', $plan) }}">
                            @csrf
                            <button type="submit" class="w-full cursor-pointer rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                                Choose {{ strtolower($label) }}
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <p class="mt-8 text-center text-sm text-gray-500">Payments and subscription management are securely handled by Stripe.</p>
</div>