<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Subscriptions')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function filterByStatus(string $status): void
    {
        abort_unless(in_array($status, ['all', 'active', 'grace', 'past_due', 'ended'], true), 422);

        $this->status = $status;
        $this->resetPage();
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription = $this->authorizedSubscription($subscription);

        $this->runStripeAction(
            fn () => $subscription->cancel(),
            'The subscription will end after its current billing period.',
        );
    }

    public function cancelNow(Subscription $subscription): void
    {
        $subscription = $this->authorizedSubscription($subscription);

        $this->runStripeAction(
            fn () => $subscription->cancelNow(),
            'The subscription was canceled immediately.',
        );
    }

    public function resume(Subscription $subscription): void
    {
        $subscription = $this->authorizedSubscription($subscription);
        abort_unless($subscription->onGracePeriod(), 422);

        $this->runStripeAction(
            fn () => $subscription->resume(),
            'The subscription was resumed.',
        );
    }

    public function with(): array
    {
        Gate::authorize('manage-subscriptions');

        $plans = SubscriptionPlan::query()->get()->keyBy('stripe_price_id');

        $subscriptions = Subscription::query()
            ->with('user')
            ->orderBy('subscriptions.id', 'desc')
            ->where('type', 'premium')
            ->when($this->search !== '', function ($query): void {
                $query->whereIn(
                    'user_id',
                    User::query()
                        ->where(function ($userQuery): void {
                            $userQuery
                                ->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('email', 'like', '%'.$this->search.'%');
                        })
                        ->select('id'),
                );
            })
            ->when($this->status === 'active', fn ($query) => $query->active()->whereNull('ends_at'))
            ->when($this->status === 'grace', fn ($query) => $query->onGracePeriod())
            ->when($this->status === 'past_due', fn ($query) => $query->where('stripe_status', 'past_due'))
            ->when($this->status === 'ended', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<=', now()));

        return [
            'subscriptions' => $subscriptions->paginate(15),
            'plans' => $plans,
            'stats' => $this->subscriptionStats($plans),
            'stripeConfigured' => filled(config('cashier.secret')),
            'stripeTestMode' => ! str_starts_with((string) config('cashier.secret'), 'sk_live_'),
        ];
    }

    /**
     * @param  Collection<string|int, SubscriptionPlan>  $plans
     * @return array{
     *     total: int,
     *     active: int,
     *     grace: int,
     *     past_due: int,
     *     ended: int,
     *     new_this_month: int,
     *     monthly_active: int,
     *     yearly_active: int,
     *     formatted_mrr: string,
     *     monthly_share: int,
     *     yearly_share: int,
     *     active_share: int,
     *     grace_share: int,
     *     past_due_share: int
     * }
     */
    private function subscriptionStats(Collection $plans): array
    {
        $now = now();

        $counts = Subscription::query()
            ->where('type', 'premium')
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when ends_at is null and stripe_status not in ('incomplete', 'incomplete_expired', 'past_due', 'unpaid', 'canceled') then 1 end) as active")
            ->selectRaw('count(case when ends_at is not null and ends_at > ? then 1 end) as grace', [$now])
            ->selectRaw("count(case when stripe_status = 'past_due' then 1 end) as past_due")
            ->selectRaw('count(case when ends_at is not null and ends_at <= ? then 1 end) as ended', [$now])
            ->selectRaw('count(case when created_at >= ? then 1 end) as new_this_month', [$now->copy()->startOfMonth()])
            ->first();

        $activeByPrice = Subscription::query()
            ->where('type', 'premium')
            ->active()
            ->whereNull('ends_at')
            ->toBase()
            ->selectRaw('stripe_price, count(*) as aggregate')
            ->groupBy('stripe_price')
            ->pluck('aggregate', 'stripe_price');

        $monthlyActive = 0;
        $yearlyActive = 0;
        $mrr = 0.0;

        foreach ($activeByPrice as $priceId => $count) {
            $count = (int) $count;
            $plan = $plans->get($priceId);

            if ($plan?->billing_interval === 'year') {
                $yearlyActive += $count;
                $mrr += $count * ((float) $plan->price_amount / 12);
            } else {
                $monthlyActive += $count;
                $mrr += $count * (float) ($plan?->price_amount ?? 0);
            }
        }

        $currency = strtoupper((string) ($plans->first()?->currency ?: config('cashier.currency', 'egp')));
        $total = (int) ($counts->total ?? 0);
        $active = (int) ($counts->active ?? 0);
        $grace = (int) ($counts->grace ?? 0);
        $pastDue = (int) ($counts->past_due ?? 0);
        $planTotal = $monthlyActive + $yearlyActive;

        return [
            'total' => $total,
            'active' => $active,
            'grace' => $grace,
            'past_due' => $pastDue,
            'ended' => (int) ($counts->ended ?? 0),
            'new_this_month' => (int) ($counts->new_this_month ?? 0),
            'monthly_active' => $monthlyActive,
            'yearly_active' => $yearlyActive,
            'formatted_mrr' => $currency.' '.number_format($mrr, 2),
            'monthly_share' => $planTotal > 0 ? (int) round(($monthlyActive / $planTotal) * 100) : 0,
            'yearly_share' => $planTotal > 0 ? (int) round(($yearlyActive / $planTotal) * 100) : 0,
            'active_share' => $total > 0 ? (int) round(($active / $total) * 100) : 0,
            'grace_share' => $total > 0 ? (int) round(($grace / $total) * 100) : 0,
            'past_due_share' => $total > 0 ? (int) round(($pastDue / $total) * 100) : 0,
        ];
    }

    private function authorizedSubscription(Subscription $subscription): Subscription
    {
        Gate::authorize('manage-subscriptions');
        abort_unless($subscription->type === 'premium', 404);
        abort_if(blank(config('cashier.secret')), 503, 'Stripe is not configured.');

        return $subscription;
    }

    private function runStripeAction(\Closure $action, string $successMessage): void
    {
        try {
            $action();
            session()->flash('success', $successMessage);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('subscription', 'Stripe could not complete this action. Try again shortly.');
        }
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subscriptions</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor Premium members, estimated revenue, and Stripe billing health.</p>
        </div>
        <a href="{{ route('admin-subscriptions.plans') }}" wire:navigate class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Plan settings
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @error('subscription')
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
            {{ $message }}
        </div>
    @enderror

    @unless ($stripeConfigured)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
            Stripe is not connected. Subscription actions are unavailable until the server keys are configured.
        </div>
    @endunless

    <div class="mb-8 grid items-stretch gap-4 lg:grid-cols-5">
        <section class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 lg:col-span-2">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <flux:icon.credit-card class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Estimated MRR</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Active Premium revenue, yearly plans averaged monthly</p>
                </div>
            </div>

            <p class="mt-6 text-4xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $stats['formatted_mrr'] }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ number_format($stats['active']) }} paying members
            </p>

            <div class="mt-6 flex divide-x divide-gray-200 dark:divide-white/10">
                <button type="button" wire:click="filterByStatus('all')" class="flex-1 cursor-pointer pr-4 text-left">
                    <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['total']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total</p>
                </button>
                <div class="flex-1 px-4">
                    <p class="text-3xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['new_this_month']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">New this month</p>
                </div>
                <button type="button" wire:click="filterByStatus('ended')" class="flex-1 cursor-pointer pl-4 text-left">
                    <p class="text-3xl font-semibold tracking-tight {{ $stats['ended'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-950 dark:text-white' }}">{{ number_format($stats['ended']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ended</p>
                </button>
            </div>
        </section>

        <section class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 lg:col-span-3">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                    <flux:icon.chart-bar class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Billing health</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Click a number to filter the table</p>
                </div>
            </div>

            <div class="mt-6 flex divide-x divide-gray-200 dark:divide-white/10">
                <button type="button" wire:click="filterByStatus('active')" class="flex-1 cursor-pointer pr-4 text-left">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <flux:icon.check-circle class="size-4" />
                    </div>
                    <p class="text-3xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['active']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Active</p>
                </button>
                <button type="button" wire:click="filterByStatus('grace')" class="flex-1 cursor-pointer px-4 text-left">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300">
                        <flux:icon.clock class="size-4" />
                    </div>
                    <p class="text-3xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">{{ number_format($stats['grace']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ending soon</p>
                </button>
                <button type="button" wire:click="filterByStatus('past_due')" class="flex-1 cursor-pointer pl-4 text-left">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                        <flux:icon.exclamation-triangle class="size-4" />
                    </div>
                    <p class="text-3xl font-semibold tracking-tight {{ $stats['past_due'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-950 dark:text-white' }}">{{ number_format($stats['past_due']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Past due</p>
                </button>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Status mix</span>
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $stats['active_share'] }}% active</span>
                </div>
                <div class="flex h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full bg-emerald-500" style="width: {{ $stats['active_share'] }}%"></div>
                    <div class="h-full bg-amber-400" style="width: {{ $stats['grace_share'] }}%"></div>
                    <div class="h-full bg-rose-500" style="width: {{ $stats['past_due_share'] }}%"></div>
                </div>
            </div>

            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">{{ number_format($stats['monthly_active']) }} monthly · {{ number_format($stats['yearly_active']) }} yearly</span>
                    <span class="font-medium text-gray-700 dark:text-gray-200">Plan mix</span>
                </div>
                <div class="flex h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full bg-indigo-500" style="width: {{ $stats['monthly_share'] }}%"></div>
                    <div class="h-full bg-violet-500" style="width: {{ $stats['yearly_share'] }}%"></div>
                </div>
            </div>
        </section>
    </div>

    <div class="mb-5 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5 sm:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or email..." class="w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-md">
        <select wire:model.live="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-52">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="grace">Ending soon</option>
            <option value="past_due">Past due</option>
            <option value="ended">Ended</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Member</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Plan</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Renewal / End</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($subscriptions as $subscription)
                    @php
                        $statusLabel = match (true) {
                            $subscription->onGracePeriod() => 'Ending soon',
                            $subscription->ended() => 'Ended',
                            $subscription->pastDue() => 'Past due',
                            $subscription->active() => 'Active',
                            default => ucfirst($subscription->stripe_status),
                        };
                        $statusClasses = match ($statusLabel) {
                            'Active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                            'Ending soon' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                            'Past due', 'Ended' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                            default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                        };
                        $stripeBase = $stripeTestMode ? 'https://dashboard.stripe.com/test' : 'https://dashboard.stripe.com';
                        $plan = $plans->get($subscription->stripe_price);
                    @endphp
                    <tr wire:key="subscription-{{ $subscription->id }}" class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                                    {{ $subscription->user?->initials() ?? '?' }}
                                </div>
                                <div>
                                    @if ($subscription->user)
                                        <a href="{{ route('admin-subscriptions.show', $subscription->user) }}" wire:navigate class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-300">
                                            {{ $subscription->user->name }}
                                        </a>
                                    @else
                                        <p class="font-medium text-gray-900 dark:text-white">Deleted user</p>
                                    @endif
                                    <p class="text-sm text-gray-500">{{ $subscription->user?->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700 dark:text-gray-200">
                            <p>{{ $plan?->name ?? 'Premium' }}</p>
                            @if ($plan?->formattedPrice())
                                <p class="text-xs text-gray-500">{{ $plan->formattedPrice() }} / {{ $plan->billing_interval }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">
                            {{ $subscription->ends_at?->format('M j, Y') ?? 'Renews in Stripe' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($subscription->user?->stripe_id)
                                    <a href="{{ $stripeBase }}/customers/{{ $subscription->user->stripe_id }}" target="_blank" rel="noopener" class="rounded-md px-2 py-1 text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300">
                                        Stripe
                                    </a>
                                @endif
                                @if ($stripeConfigured && $subscription->onGracePeriod())
                                    <button type="button" wire:click="resume({{ $subscription->id }})" wire:confirm="Resume this subscription?" class="cursor-pointer rounded-md px-2 py-1 text-sm font-medium text-emerald-600 hover:bg-emerald-50">
                                        Resume
                                    </button>
                                @elseif ($stripeConfigured && $subscription->active())
                                    <button type="button" wire:click="cancel({{ $subscription->id }})" wire:confirm="Cancel at the end of the billing period?" class="cursor-pointer rounded-md px-2 py-1 text-sm font-medium text-amber-600 hover:bg-amber-50">
                                        Cancel
                                    </button>
                                    <button type="button" wire:click="cancelNow({{ $subscription->id }})" wire:confirm="Cancel immediately? Access will end now." class="cursor-pointer rounded-md px-2 py-1 text-sm font-medium text-red-600 hover:bg-red-50">
                                        Cancel now
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">No subscriptions match these filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $subscriptions->links() }}</div>
</div>
