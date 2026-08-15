<div class="bg-indigo-600 p-8">
    <div class="max-w-xl mx-auto text-center">
        <h2 class="text-2xl font-bold text-white mb-2">Stay Updated</h2>
        <p class="text-indigo-100 mb-6">Get notified when we publish new posts. No spam, unsubscribe anytime.</p>

        @if (session('subscribe-success'))
            <div class="bg-white rounded-lg p-4 mb-4" wire:transition>
                <p class="text-sm text-green-800">{{ session('subscribe-success') }}</p>
            </div>
        @endif

        <form wire:submit="subscribe" class="flex flex-col sm:flex-row gap-3">
            <input
                type="email"
                wire:model="email"
                placeholder="Enter your email"
                class="flex-1 text-white rounded-md border-transparent focus:border-white focus:ring-white"
            />
            <button
                type="submit"
                class="px-6 py-3 bg-white text-indigo-600 rounded-md font-semibold hover:bg-indigo-50 transition"
            >
                Subscribe
            </button>
        </form>

        @error('email')
        <p class="mt-2 text-sm text-white">{{ $message }}</p>
        @enderror
    </div>
</div>
