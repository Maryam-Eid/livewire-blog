<?php

use App\Http\Controllers\SubscriptionController;
use App\Livewire\PostList;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::livewire('dashboard', 'pages::dashboard')
    ->middleware(['auth', 'verified', 'can:create-post'])
    ->name('dashboard');

Route::get('/subscribe/verify/{token}', function (string $token) {
    $subscriber = Subscriber::query()
        ->where('token', $token)
        ->firstOrFail();

    if (! $subscriber->is_verified || $subscriber->verified_at === null) {
        $subscriber->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    return view('subscription-verified');
})->middleware('signed')->name('subscribers.verify');

// Unsubscribe
Route::get('/unsubscribe/{token}', function ($token) {
    $subscriber = Subscriber::where('token', $token)->firstOrFail();
    if ($subscriber) {
        $subscriber->delete();

        return view('unsubscribed');
    }

    abort(404);
})->name('unsubscribe');

// Blog
Route::get('/', fn () => redirect('/blog'))->name('home');
Route::get('/blog', PostList::class)->name('blog.index');
Route::livewire('/blog/{slug}', 'pages::posts.show')->name('blog.show');
Route::livewire('/pricing', 'pages::pricing')->name('pricing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/billing/checkout/{plan}', [SubscriptionController::class, 'checkout'])
        ->name('billing.checkout');
    Route::post('/billing/portal', [SubscriptionController::class, 'portal'])
        ->name('billing.portal');
    Route::livewire('/billing/success', 'pages::billing.success')
        ->name('billing.success');
    Route::livewire('/billing/cancel', 'pages::billing.cancel')
        ->name('billing.cancel');
});

Route::middleware('auth')->group(function () {
    // Posts
    Route::livewire('/posts', 'pages::posts.index')
        ->middleware('can:create-post')
        ->name('posts.index');

    Route::livewire('/posts/create', 'pages::posts.create')
        ->middleware('can:create-post')
        ->name('posts.create');

    Route::livewire('/posts/{post}/edit', 'pages::posts.edit')
        ->middleware('can:create-post')
        ->name('posts.edit');

    // Users
    Route::livewire('/users', 'pages::users.index')
        ->middleware('can:manage-users')
        ->name('users.index');

    Route::livewire('/users/create', 'pages::users.create')
        ->middleware('can:manage-users')
        ->name('users.create');

    Route::livewire('/users/{user}/edit', 'pages::users.edit')
        ->middleware('can:manage-users')
        ->name('users.edit');

    // Subscriptions
    Route::livewire('/subscriptions', 'pages::subscriptions.index')
        ->middleware('can:manage-subscriptions')
        ->name('admin-subscriptions.index');

    Route::livewire('/subscriptions/plans', 'pages::subscriptions.plans')
        ->middleware('can:manage-subscriptions')
        ->name('admin-subscriptions.plans');

    Route::livewire('/subscriptions/{user}', 'pages::subscriptions.show')
        ->middleware('can:manage-subscriptions')
        ->name('admin-subscriptions.show');

    // Categories
    Route::livewire('/categories', 'pages::categories.index')
        ->middleware('can:manage-roles')
        ->name('categories.index');

    Route::livewire('/categories/create', 'pages::categories.create')
        ->middleware('can:manage-roles')
        ->name('categories.create');

    Route::livewire('/categories/{category}/edit', 'pages::categories.edit')
        ->middleware('can:manage-roles')
        ->name('categories.edit');

    // Tags
    Route::livewire('/tags', 'pages::tags.index')
        ->middleware('can:manage-roles')
        ->name('tags.index');

    Route::livewire('/tags/create', 'pages::tags.create')
        ->middleware('can:manage-roles')
        ->name('tags.create');

    Route::livewire('/tags/{tag}/edit', 'pages::tags.edit')
        ->middleware('can:manage-roles')
        ->name('tags.edit');

    // comments
    Route::livewire('/comments', 'pages::comments.index')
        ->middleware('can:create-post')
        ->name('comments.index');

    // Newsletters
    Route::livewire('/newsletters', 'pages::newsletters.index')
        ->middleware('can:manage-newsletters')
        ->name('newsletters.index');

    Route::livewire('/newsletters/create', 'pages::newsletters.create')
        ->middleware('can:manage-newsletters')
        ->name('newsletters.create');

    Route::livewire('/newsletters/{newsletter}/edit', 'pages::newsletters.edit')
        ->middleware('can:manage-newsletters')
        ->name('newsletters.edit');
});

require __DIR__.'/settings.php';
