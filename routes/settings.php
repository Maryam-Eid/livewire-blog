<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('account', 'account/profile')->name('account');
    Route::redirect('settings', 'account/profile');
    Route::redirect('settings/profile', 'account/profile');
    Route::redirect('settings/security', 'account/security');
    Route::redirect('settings/billing', 'account/billing');

    Route::livewire('account/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('account/billing', 'pages::settings.billing')->name('billing.edit');

    Route::livewire('settings/appearance', 'pages::settings.appearance')
        ->middleware('can:create-post')
        ->name('appearance.edit');

    Route::livewire('account/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
