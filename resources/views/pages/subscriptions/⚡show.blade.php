<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Subscriber Details')] class extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        Gate::authorize('manage-subscriptions');
        $this->user = $user;
    }

    public function with(): array
    {
        Gate::authorize('manage-subscriptions');

        $subscription = $this->user->subscription('premium');
        $invoices = new Collection;
        $invoiceError = null;

        if (filled(config('cashier.secret')) && filled($this->user->stripe_id)) {
            try {
                $invoices = $this->user->invoicesIncludingPending(['limit' => 10]);
            } catch (\Throwable $exception) {
                report($exception);
                $invoiceError = 'Stripe invoices could not be loaded right now.';
            }
        }

        return [
            'subscription' => $subscription,
            'plan' => $subscription === null
                ? null
                : SubscriptionPlan::query()
                    ->where('stripe_price_id', $subscription->stripe_price)
                    ->first(),
            'invoices' => $invoices,
            'invoiceError' => $invoiceError,
            'stripeTestMode' => ! str_starts_with((string) config('cashier.secret'), 'sk_live_'),
        ];
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin-subscriptions.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 dark:text-indigo-300">← Subscriptions</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
        </div>

        @if ($user->stripe_id)
            @php($stripeBase = $stripeTestMode ? 'https://dashboard.stripe.com/test' : 'https://dashboard.stripe.com')
            <a href="{{ $stripeBase }}/customers/{{ $user->stripe_id }}" target="_blank" rel="noopener" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Open in Stripe
            </a>
        @endif
    </div>

    <section class="mb-8 grid gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Plan</p>
            <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $plan?->name ?? 'No Premium plan' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</p>
            <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $subscription ? ucfirst($subscription->stripe_status) : 'Not subscribed' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Stripe customer</p>
            <p class="mt-2 break-all font-mono text-sm text-gray-700 dark:text-gray-300">{{ $user->stripe_id ?? 'Not created' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ends at</p>
            <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $subscription?->ends_at?->format('M j, Y') ?? '—' }}</p>
        </div>
    </section>

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent invoices</h2>
        <p class="mt-1 text-sm text-gray-500">The latest invoices and payment status reported by Stripe.</p>
    </div>

    @if ($invoiceError)
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
            {{ $invoiceError }}
        </div>
    @elseif (blank(config('cashier.secret')))
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
            Connect Stripe to load invoices.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Invoice</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($invoices as $invoice)
                        @php($stripeInvoice = $invoice->asStripeInvoice())
                        <tr wire:key="invoice-{{ $stripeInvoice->id }}">
                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->date()->format('M j, Y') }}</td>
                            <td class="px-5 py-4 font-medium text-gray-900 dark:text-white">{{ $invoice->total() }}</td>
                            <td class="px-5 py-4 text-sm capitalize text-gray-700 dark:text-gray-200">{{ $stripeInvoice->status }}</td>
                            <td class="px-5 py-4 text-right">
                                @if ($stripeInvoice->hosted_invoice_url)
                                    <a href="{{ $stripeInvoice->hosted_invoice_url }}" target="_blank" rel="noopener" class="text-sm font-medium text-indigo-600 dark:text-indigo-300">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No invoices found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>