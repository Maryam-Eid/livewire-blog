<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Checkout Canceled')] class extends Component
{
};
?>

<div class="mx-auto max-w-2xl px-4 pb-20 text-center sm:px-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-10 shadow-sm">
        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-100 text-2xl font-bold text-gray-500">×</div>
        <h1 class="mt-5 text-3xl font-bold text-gray-950">Checkout canceled</h1>
        <p class="mt-4 text-gray-600">No payment was taken. You can return to the plans whenever you are ready.</p>
        <a href="{{ route('pricing') }}" wire:navigate class="mt-7 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700">
            Back to plans
        </a>
    </div>
</div>