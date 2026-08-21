<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
<nav class="sticky top-0 z-50 overflow-hidden border-b border-white/20 bg-gradient-to-r from-indigo-600/80 via-purple-600/80 to-indigo-600/80 backdrop-blur-md">
    <!-- Subtle wave -->
    <div class="pointer-events-none absolute inset-0">
        <svg class="absolute bottom-0 left-0 w-full text-white/5" viewBox="0 0 1440 100" preserveAspectRatio="none" fill="currentColor">
            <path d="M0,60 C480,110 960,10 1440,60 L1440,100 L0,100 Z" />
        </svg>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ route('blog.index') }}" class="flex items-center gap-2.5" wire:navigate>
                <img src="{{ asset('favicon.svg') }}" alt="{{ config('app.name') }}"
                     class="h-10 w-10 object-contain">
                <span class="text-xl font-bold text-white" style="font-family: 'Playfair Display', serif;">
                    {{ config('app.name') }}
                </span>
            </a>

            <div class="flex items-center gap-6">
                @auth
                    <a href="{{ route('dashboard') }}"
                       wire:navigate
                       class="group relative text-sm text-indigo-100 transition-colors duration-200 hover:text-white">
                        Dashboard
                        <span class="absolute -bottom-1 left-0 h-px w-0 bg-white transition-all duration-300 group-hover:w-full"></span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       wire:navigate
                       class="group relative text-sm text-indigo-100 transition-colors duration-200 hover:text-white">
                        Login
                        <span class="absolute -bottom-1 left-0 h-px w-0 bg-white transition-all duration-300 group-hover:w-full"></span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main class="pt-10">
    {{ $slot }}
</main>

<footer class="bg-white border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <p class="text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
    </div>
</footer>
</body>
</html>
