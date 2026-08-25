<nav class="sticky top-0 z-50 border-b border-white/20 bg-gradient-to-r from-indigo-600/80 via-purple-600/80 to-indigo-600/80 backdrop-blur-md">
    <div class="pointer-events-none absolute inset-0">
        <svg class="absolute bottom-0 left-0 w-full text-white/5" viewBox="0 0 1440 100" preserveAspectRatio="none" fill="currentColor">
            <path d="M0,60 C480,110 960,10 1440,60 L1440,100 L0,100 Z" />
        </svg>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ route('blog.index') }}" class="flex items-center gap-2.5" wire:navigate>
                <img src="{{ asset('favicon.svg') }}" alt="{{ config('app.name') }}" class="h-10 w-10 object-contain">
                <span class="text-xl font-bold text-white" style="font-family: 'Playfair Display', serif;">
                    {{ config('app.name') }}
                </span>
            </a>

            <div class="flex items-center gap-6">
                @auth
                    @if (auth()->user()->can('create-post'))
                        <a href="{{ route('dashboard') }}" wire:navigate class="group relative text-sm text-indigo-100 transition-colors duration-200 hover:text-white">
                            Dashboard
                            <span class="absolute -bottom-1 left-0 h-px w-0 bg-white transition-all duration-300 group-hover:w-full"></span>
                        </a>
                    @else
                        <a href="{{ route('pricing') }}" wire:navigate class="group relative text-sm text-indigo-100 transition-colors duration-200 hover:text-white">
                            Premium
                            <span class="absolute -bottom-1 left-0 h-px w-0 bg-white transition-all duration-300 group-hover:w-full"></span>
                        </a>

                        <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                            <button
                                type="button"
                                x-on:click="open = ! open"
                                x-bind:aria-expanded="open"
                                aria-label="Profile"
                                title="Profile"
                                class="navbar-avatar flex size-10 cursor-pointer items-center justify-center rounded-full border border-white/80 text-white transition bg-indigo-600 hover:bg-indigo-500"
                            >
                                <span class="text-s font-semibold tracking-wide">{{ auth()->user()->initials() }}</span>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1"
                                class="absolute right-0 top-full z-[60] mt-3 w-72 overflow-hidden rounded-2xl border border-white/20 bg-white shadow-2xl shadow-indigo-950/20"
                            >
                                <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>

                                <div class="p-2">
                                    <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                                        <svg class="size-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                        Profile
                                    </a>
                                    <a href="{{ route('billing.edit') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                                        <svg class="size-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                        </svg>
                                        Billing
                                    </a>
                                    <a href="{{ route('security.edit') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                                        <svg class="size-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                        Security
                                    </a>
                                </div>

                                <div class="border-t border-gray-100 p-2">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                            </svg>
                                            Log out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <a href="{{ route('pricing') }}" wire:navigate class="group relative text-sm text-indigo-100 transition-colors duration-200 hover:text-white">
                        Premium
                        <span class="absolute -bottom-1 left-0 h-px w-0 bg-white transition-all duration-300 group-hover:w-full"></span>
                    </a>

                    <a href="{{ route('login') }}" wire:navigate class="group relative text-sm text-indigo-100 transition-colors duration-200 hover:text-white">
                        Login
                        <span class="absolute -bottom-1 left-0 h-px w-0 bg-white transition-all duration-300 group-hover:w-full"></span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
