<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 dark:bg-zinc-950">
<main class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-xl dark:border-white/10 dark:bg-zinc-900">
        <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
            <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subscription confirmed</h1>
        <p class="mt-3 text-gray-600 dark:text-gray-400">
            Your email is verified. You will now receive new posts and newsletters.
        </p>
        <a
            href="{{ route('blog.index') }}"
            class="mt-7 inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white transition hover:bg-indigo-700"
        >
            Back to Blog
        </a>
    </div>
</main>
</body>
</html>
