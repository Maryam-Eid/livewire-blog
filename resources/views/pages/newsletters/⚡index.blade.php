<?php

use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $activeTab = 'campaigns';

    public string $statusFilter = 'all';

    public string $subscriberSearch = '';

    public function with(): array
    {
        $newslettersQuery = Newsletter::query()
            ->with('user')
            ->withCount([
                'deliveries as actual_recipient_count',
                'deliveries as actual_sent_count' => fn ($query) => $query->where('status', 'sent'),
                'deliveries as actual_failed_count' => fn ($query) => $query->where('status', 'failed'),
            ])
            ->when(
                $this->statusFilter !== 'all',
                fn ($query) => $query->where('status', $this->statusFilter),
            )
            ->latest();

        $subscribersQuery = Subscriber::query()
            ->when(
                $this->subscriberSearch,
                fn ($query) => $query->where('email', 'like', '%'.$this->subscriberSearch.'%'),
            )
            ->latest();

        return [
            'stats' => [
                'subscribers' => Subscriber::query()->count(),
                'verified_subscribers' => Subscriber::query()->verified()->count(),
                'sent_campaigns' => Newsletter::query()->where('status', 'sent')->count(),
                'emails_sent' => NewsletterDelivery::query()->where('status', 'sent')->count(),
                'failed_emails' => NewsletterDelivery::query()->where('status', 'failed')->count(),
            ],
            'newsletters' => $newslettersQuery->paginate(10, pageName: 'newslettersPage'),
            'subscribers' => $subscribersQuery->paginate(10, pageName: 'subscribersPage'),
        ];
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['campaigns', 'subscribers'], true), 404);

        $this->activeTab = $tab;
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage(pageName: 'newslettersPage');
    }

    public function updatingSubscriberSearch(): void
    {
        $this->resetPage(pageName: 'subscribersPage');
    }

    public function deleteNewsletter(Newsletter $newsletter): void
    {
        Gate::authorize('manage-newsletters');
        abort_unless(in_array($newsletter->status, ['draft', 'scheduled'], true), 422);

        $newsletter->delete();
        session()->flash('success', 'Newsletter deleted successfully.');
    }

    public function deleteSubscriber(Subscriber $subscriber): void
    {
        Gate::authorize('manage-newsletters');

        $subscriber->delete();
        session()->flash('success', 'Subscriber removed successfully.');
    }
};
?>

<div wire:poll.2s>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Newsletter</h1>
            <p class="mt-1 text-sm text-gray-600">Create campaigns, monitor delivery, and manage subscribers</p>
        </div>

        <a
            href="{{ route('newsletters.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
        >
            Create Newsletter
        </a>
    </div>

    <div class="mb-8 grid gap-4 lg:grid-cols-5">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 lg:col-span-2">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <flux:icon.users class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Audience</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Your current subscriber base</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 divide-x divide-gray-200 dark:divide-white/10">
                <div class="pr-5">
                    <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['subscribers']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total subscribers</p>
                </div>
                <div class="pl-5">
                    <p class="text-3xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['verified_subscribers']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Verified</p>
                </div>
            </div>

            @php
                $verifiedPercentage = $stats['subscribers'] > 0
                    ? round(($stats['verified_subscribers'] / $stats['subscribers']) * 100)
                    : 0;
            @endphp
            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Verification rate</span>
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $verifiedPercentage }}%</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $verifiedPercentage }}%"></div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 lg:col-span-3">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                    <flux:icon.chart-bar class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Campaign performance</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">A quick overview of newsletter delivery</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-3 divide-x divide-gray-200 dark:divide-white/10">
                <div class="pr-4">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                        <flux:icon.paper-airplane class="size-4" />
                    </div>
                    <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['sent_campaigns']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sent campaigns</p>
                </div>
                <div class="px-4">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300">
                        <flux:icon.envelope class="size-4" />
                    </div>
                    <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['emails_sent']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Emails sent</p>
                </div>
                <div class="pl-4">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                        <flux:icon.exclamation-triangle class="size-4" />
                    </div>
                    <p class="text-3xl font-semibold tracking-tight {{ $stats['failed_emails'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-950 dark:text-white' }}">{{ number_format($stats['failed_emails']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Failed deliveries</p>
                </div>
            </div>
        </section>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4" wire:transition>
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="mb-6 flex gap-2 border-b border-gray-200">
        <button
            type="button"
            wire:click="setTab('campaigns')"
            class="cursor-pointer border-b-2 px-4 py-3 text-sm font-medium transition {{ $activeTab === 'campaigns' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
        >
            Campaign history
        </button>
        <button
            type="button"
            wire:click="setTab('subscribers')"
            class="cursor-pointer border-b-2 px-4 py-3 text-sm font-medium transition {{ $activeTab === 'subscribers' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
        >
            Subscribers
        </button>
    </div>

    @if ($activeTab === 'campaigns')
        <div class="mb-4 flex justify-end">
            <select
                wire:model.live="statusFilter"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-52"
            >
                <option value="all">All statuses</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="sending">Sending</option>
                <option value="sent">Sent</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Delivery</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($newsletters as $newsletter)
                        <tr wire:key="newsletter-{{ $newsletter->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $newsletter->subject }}</p>
                                <p class="mt-1 text-xs text-gray-500">Created by {{ $newsletter->user->name }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $newsletter->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $newsletter->status === 'scheduled' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $newsletter->status === 'sending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $newsletter->status === 'sent' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $newsletter->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}"
                                >
                                    {{ ucfirst($newsletter->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>{{ number_format($newsletter->actual_sent_count) }} sent / {{ number_format($newsletter->actual_recipient_count) }} recipients</div>
                                @if ($newsletter->actual_failed_count > 0)
                                    <div class="mt-1 text-xs text-red-600">{{ number_format($newsletter->actual_failed_count) }} failed</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                @if ($newsletter->status === 'scheduled')
                                    {{ $newsletter->scheduled_at?->format('M d, Y · g:i A') }}
                                @elseif ($newsletter->sent_at)
                                    {{ $newsletter->sent_at->format('M d, Y · g:i A') }}
                                @else
                                    {{ $newsletter->created_at->format('M d, Y') }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                @if (in_array($newsletter->status, ['draft', 'scheduled'], true))
                                    <a
                                        href="{{ route('newsletters.edit', $newsletter) }}"
                                        wire:navigate
                                        class="rounded-md px-2 py-1 font-medium text-indigo-600 hover:bg-indigo-100"
                                    >
                                        Edit
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="deleteNewsletter({{ $newsletter->id }})"
                                        wire:confirm="Delete this newsletter?"
                                        class="cursor-pointer rounded-md px-2 py-1 font-medium text-red-600 hover:bg-red-100"
                                    >
                                        Delete
                                    </button>
                                @else
                                    <span class="text-gray-400">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                No newsletter campaigns yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $newsletters->links() }}</div>
    @else
        <div class="mb-4">
            <input
                type="search"
                wire:model.live.debounce.300ms="subscriberSearch"
                placeholder="Search subscribers by email..."
                class="w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-md"
            >
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subscribed</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($subscribers as $subscriber)
                        <tr wire:key="subscriber-{{ $subscriber->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $subscriber->email }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $subscriber->is_verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $subscriber->is_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $subscriber->created_at->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <button
                                    type="button"
                                    wire:click="deleteSubscriber({{ $subscriber->id }})"
                                    wire:confirm="Remove this subscriber?"
                                    class="cursor-pointer rounded-md px-2 py-1 text-sm font-medium text-red-600 hover:bg-red-100"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No subscribers found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $subscribers->links() }}</div>
    @endif
</div>
