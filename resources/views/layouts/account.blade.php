<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
@include('partials.public-navbar')

<main class="mx-auto min-h-[calc(100vh-8rem)] max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    {{ $slot }}
</main>

<footer class="border-t border-zinc-200 bg-white py-4 dark:border-white/10 dark:bg-zinc-900">
    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </p>
</footer>

@persist('toast')
<flux:toast.group>
    <flux:toast />
</flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>
