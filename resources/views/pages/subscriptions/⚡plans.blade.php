<?php

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Cashier\Cashier;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Exception\InvalidRequestException;

new #[Title('Subscription Plans')] class extends Component
{
    /** @var array<string, array<string, mixed>> */
    public array $plans = [];

    public function mount(): void
    {
        Gate::authorize('manage-subscriptions');

        $storedPlans = SubscriptionPlan::query()->get()->keyBy('key');

        foreach ([
            'monthly' => ['name' => 'Monthly', 'billing_interval' => 'month'],
            'yearly' => ['name' => 'Yearly', 'billing_interval' => 'year'],
        ] as $key => $defaults) {
            $plan = $storedPlans->get($key);

            $this->plans[$key] = [
                'name' => $plan?->name ?? $defaults['name'],
                'stripe_price_id' => $plan?->stripe_price_id ?? '',
                'price_amount' => $plan?->price_amount ?? '',
                'currency' => $plan?->currency ?? strtoupper((string) config('cashier.currency', 'egp')),
                'billing_interval' => $defaults['billing_interval'],
                'is_active' => $plan?->is_active ?? false,
            ];
        }
    }

    public function save(): void
    {
        Gate::authorize('manage-subscriptions');

        $validated = Validator::make($this->plans, [
            'monthly' => ['required', 'array'],
            'yearly' => ['required', 'array'],
            '*.name' => ['required', 'string', 'max:100'],
            '*.stripe_price_id' => ['nullable', 'string', 'starts_with:price_', 'max:255'],
            '*.price_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            '*.currency' => ['required', 'string', 'size:3'],
            '*.billing_interval' => ['required', 'in:month,year'],
            '*.is_active' => ['required', 'boolean'],
        ])->validate();

        foreach (['monthly', 'yearly'] as $key) {
            if ($validated[$key]['is_active'] && blank($validated[$key]['stripe_price_id'])) {
                $this->addError("plans.{$key}.stripe_price_id", 'An active plan requires a Stripe Price ID.');

                return;
            }
        }

        $billingCurrency = strtolower((string) config('cashier.currency'));

        if (filled(config('cashier.secret')) && ! app()->runningUnitTests()) {
            foreach (['monthly', 'yearly'] as $key) {
                $priceId = $validated[$key]['stripe_price_id'];

                if (blank($priceId)) {
                    continue;
                }

                try {
                    $stripeCurrency = strtolower(Cashier::stripe()->prices->retrieve($priceId)->currency);
                } catch (InvalidRequestException) {
                    $this->addError("plans.{$key}.stripe_price_id", 'Stripe could not find this Price ID.');

                    return;
                }

                if ($stripeCurrency !== $billingCurrency) {
                    $this->addError(
                        "plans.{$key}.stripe_price_id",
                        'This Stripe Price is in '.strtoupper($stripeCurrency).', but billing is '.strtoupper($billingCurrency).'.',
                    );

                    return;
                }

                $validated[$key]['currency'] = strtoupper($billingCurrency);
            }
        }

        foreach (['monthly', 'yearly'] as $key) {
            SubscriptionPlan::query()->updateOrCreate(
                ['key' => $key],
                [
                    ...$validated[$key],
                    'currency' => strtoupper($validated[$key]['currency']),
                ],
            );
        }

        session()->flash('success', 'Subscription plans updated.');
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subscription Plans</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Configure the plans shown to readers. Stripe secret keys remain in the server environment.</p>
        </div>
        <a href="{{ route('admin-subscriptions.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-white/15 dark:text-gray-200">
            Manage subscribers
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if (blank(config('cashier.secret')))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
            Stripe is not connected. Add <code>STRIPE_SECRET</code> to the server environment before enabling checkout.
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @foreach (['monthly' => 'Monthly plan', 'yearly' => 'Yearly plan'] as $key => $heading)
            <section wire:key="plan-{{ $key }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $heading }}</h2>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                        <input type="checkbox" wire:model="plans.{{ $key }}.is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Display name</label>
                        <input type="text" wire:model="plans.{{ $key }}.name" class="mt-1 block w-full rounded-md border border-gray-300 p-2 dark:border-white/15 dark:bg-zinc-900">
                        @error("plans.{$key}.name") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Stripe Price ID</label>
                        <input type="text" wire:model="plans.{{ $key }}.stripe_price_id" placeholder="price_..." class="mt-1 block w-full rounded-md border border-gray-300 p-2 font-mono text-sm dark:border-white/15 dark:bg-zinc-900">
                        @error("plans.{$key}.stripe_price_id") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Displayed price</label>
                        <input type="number" min="0" step="0.01" wire:model="plans.{{ $key }}.price_amount" class="mt-1 block w-full rounded-md border border-gray-300 p-2 dark:border-white/15 dark:bg-zinc-900">
                        <p class="mt-1 text-xs text-gray-500">For display only; Stripe charges the amount configured on the Price ID.</p>
                        @error("plans.{$key}.price_amount") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Currency</label>
                        <input type="text" maxlength="3" wire:model="plans.{{ $key }}.currency" class="mt-1 block w-full rounded-md border border-gray-300 p-2 uppercase dark:border-white/15 dark:bg-zinc-900">
                        @error("plans.{$key}.currency") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endforeach

        <button type="submit" wire:loading.attr="disabled" class="cursor-pointer rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
            Save plans
        </button>
    </form>
</div>