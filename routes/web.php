<?php

use App\Livewire\PostList;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/blog'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/blog', PostList::class)->name('blog.index');
Route::livewire('/blog/{slug}', 'pages::posts.show')->name('blog.show');

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

    Route::livewire('/tags/{category}/edit', 'pages::tags.edit')
        ->middleware('can:manage-roles')
        ->name('tags.edit');

    // comments
    Route::livewire('/comments', 'pages::comments.index')
        ->middleware('can:create-post')
        ->name('comments.index');
});

require __DIR__ . '/settings.php';
