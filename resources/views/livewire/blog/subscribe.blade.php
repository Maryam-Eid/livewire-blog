<div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 to-purple-700 px-6 py-12 sm:py-16">
    <!-- Decorative wave shapes -->
    <div class="pointer-events-none absolute inset-0">
        <svg class="absolute bottom-0 left-0 w-full text-white/5" viewBox="0 0 1440 200" preserveAspectRatio="none" fill="currentColor">
            <path d="M0,100 C240,180 480,20 720,80 C960,140 1200,60 1440,110 L1440,200 L0,200 Z" />
        </svg>
        <svg class="absolute bottom-0 left-0 w-full text-white/5" viewBox="0 0 1440 200" preserveAspectRatio="none" fill="currentColor">
            <path d="M0,140 C260,80 500,180 740,120 C980,60 1220,150 1440,90 L1440,200 L0,200 Z" />
        </svg>
        <svg class="absolute top-0 left-0 w-full rotate-180 text-white/5" viewBox="0 0 1440 200" preserveAspectRatio="none" fill="currentColor">
            <path d="M0,120 C300,40 600,160 900,90 C1100,50 1300,110 1440,70 L1440,200 L0,200 Z" />
        </svg>
    </div>

    <div class="relative mx-auto max-w-xl text-center">
        <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-white/10 backdrop-blur">
            <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>

        <h2 class="mb-2 text-2xl font-bold text-white sm:text-3xl">Stay Updated</h2>
        <p class="mb-8 text-indigo-100">Get notified when we publish new posts. No spam, unsubscribe anytime.</p>

        @if (session('subscribe-success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-white p-4 shadow-sm" wire:transition>
                <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('subscribe-success') }}</p>
            </div>
        @endif

        <form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <input
                    type="email"
                    wire:model="email"
                    placeholder="Enter your email"
                    class="w-full rounded-md border-0 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600"
                />
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="subscribe"
                class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-md bg-white px-6 py-3 font-semibold text-indigo-600 shadow-sm transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg wire:loading wire:target="subscribe" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                <span wire:loading wire:target="subscribe">Subscribing...</span>
            </button>
        </form>

        @error('email')
        <p class="mt-3 text-sm font-medium text-red-300">{{ $message }}</p>
        @enderror
    </div>
</div>
