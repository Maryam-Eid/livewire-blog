<?php

use App\Jobs\DispatchNewsletter;
use App\Models\Newsletter;
use App\Models\Subscriber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public Newsletter $newsletter;

    #[Validate('required|string|min:3|max:255')]
    public string $subject = '';

    #[Validate('required|string|min:10')]
    public string $content = '';

    #[Validate('required|in:now,scheduled')]
    public string $deliveryMode = 'now';

    #[Validate('required_if:deliveryMode,scheduled|nullable|date_format:Y-m-d H:i')]
    public ?string $scheduledAt = null;

    public function mount(Newsletter $newsletter): void
    {
        Gate::authorize('manage-newsletters');
        abort_unless(in_array($newsletter->status, ['draft', 'scheduled'], true), 404);

        $this->newsletter = $newsletter;
        $this->subject = $newsletter->subject;
        $this->content = $newsletter->content;
        $this->deliveryMode = $newsletter->status === 'scheduled' ? 'scheduled' : 'now';
        $this->scheduledAt = $newsletter->scheduled_at?->format('Y-m-d H:i');
    }

    public function with(): array
    {
        return [
            'verifiedSubscriberCount' => Subscriber::query()->verified()->count(),
        ];
    }

    public function saveDraft(): void
    {
        Gate::authorize('manage-newsletters');
        $this->validateOnly('subject');
        $this->validateOnly('content');

        $this->newsletter->update([
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => 'draft',
            'scheduled_at' => null,
        ]);

        session()->flash('success', 'Newsletter draft updated.');
        $this->redirect(route('newsletters.index'), navigate: true);
    }

    public function submit(): void
    {
        Gate::authorize('manage-newsletters');
        $this->validate();

        $scheduledAt = $this->scheduledPublicationAt();
        $this->newsletter->update([
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->deliveryMode === 'scheduled' ? 'scheduled' : 'sending',
            'scheduled_at' => $scheduledAt,
        ]);

        if ($this->deliveryMode === 'now') {
            DispatchNewsletter::dispatch($this->newsletter);
            session()->flash('success', 'Newsletter queued for delivery.');
        } else {
            session()->flash('success', 'Newsletter schedule updated.');
        }

        $this->redirect(route('newsletters.index'), navigate: true);
    }

    private function scheduledPublicationAt(): ?CarbonImmutable
    {
        if ($this->deliveryMode !== 'scheduled') {
            return null;
        }

        $scheduledAt = CarbonImmutable::parse($this->scheduledAt, config('app.timezone'));

        if ($scheduledAt->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'scheduledAt' => 'Choose a future date and time.',
            ]);
        }

        return $scheduledAt;
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Newsletter</h1>
        <p class="mt-1 text-sm text-gray-600">
            Update this campaign for {{ number_format($verifiedSubscriberCount) }} verified subscribers
        </p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="submit" class="space-y-6">
            <div>
                <label for="subject" class="required-label block text-sm font-medium text-gray-700">Email subject</label>
                <input
                    id="subject"
                    type="text"
                    wire:model="subject"
                    placeholder="Enter a clear newsletter subject"
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    autofocus
                >
                @error('subject')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="newsletter-content" class="required-label block text-sm font-medium text-gray-700">Email content</label>
                <div wire:ignore class="mt-1">
                    <input id="newsletter-content" type="hidden" value="{{ $content }}">
                    <trix-editor
                        input="newsletter-content"
                        class="trix-content min-h-64"
                        x-data
                        x-on:trix-change="$wire.content = $event.target.value"
                    ></trix-editor>
                </div>
                @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="required-label mb-2 block text-sm font-medium text-gray-700">Delivery</label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300">
                        <input type="radio" wire:model.live="deliveryMode" value="now" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 font-medium text-gray-900">Send now</span>
                        <span class="mt-1 block pl-6 text-sm text-gray-500">Queue the email for all verified subscribers.</span>
                    </label>
                    <label class="cursor-pointer rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300">
                        <input type="radio" wire:model.live="deliveryMode" value="scheduled" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 font-medium text-gray-900">Schedule</span>
                        <span class="mt-1 block pl-6 text-sm text-gray-500">Choose a future delivery date and time.</span>
                    </label>
                </div>
            </div>

            @if ($deliveryMode === 'scheduled')
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800" wire:transition>
                    <label for="newsletterScheduledAt" class="required-label block text-sm font-medium text-gray-700">
                        Delivery date and time
                    </label>
                    <div
                        wire:ignore
                        x-data="{
                            picker: null,
                            init() {
                                this.picker = window.flatpickr(this.$refs.input, {
                                    enableTime: true,
                                    dateFormat: 'Y-m-d H:i',
                                    altInput: true,
                                    altFormat: 'F j, Y at h:i K',
                                    defaultDate: @js($scheduledAt),
                                    minDate: new Date(),
                                    minuteIncrement: 15,
                                    onChange: (dates, value) => $wire.set('scheduledAt', value),
                                });
                            },
                            destroy() {
                                this.picker?.destroy();
                            },
                        }"
                    >
                        <input
                            id="newsletterScheduledAt"
                            x-ref="input"
                            type="text"
                            placeholder="Choose delivery date and time"
                            class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Timezone: {{ config('app.timezone') }}</p>
                    @error('scheduledAt')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex flex-wrap gap-3 border-t border-gray-200 pt-6">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="cursor-pointer rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60"
                >
                    {{ $deliveryMode === 'scheduled' ? 'Update Schedule' : 'Send Newsletter' }}
                </button>
                <button
                    type="button"
                    wire:click="saveDraft"
                    wire:loading.attr="disabled"
                    class="cursor-pointer rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60"
                >
                    Save Draft
                </button>
                <a
                    href="{{ route('newsletters.index') }}"
                    wire:navigate
                    class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
