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
    Route::livewire('/posts', 'pages::posts.index')
        ->middleware('can:create-post')
        ->name('posts.index');

    Route::livewire('/posts/create', 'pages::posts.create')
        ->middleware('can:create-post')
        ->name('posts.create');

    Route::livewire('/posts/{post}/edit', 'pages::posts.edit')
        ->middleware('can:create-post')
        ->name('posts.edit');

    Route::livewire('/users', 'pages::users.index')
        ->middleware('can:manage-users')
        ->name('users.index');

    Route::livewire('/users/create', 'pages::users.create')
        ->middleware('can:manage-users')
        ->name('users.create');

    Route::livewire('/users/{user}/edit', 'pages::users.edit')
        ->middleware('can:manage-users')
        ->name('users.edit');
});

require __DIR__ . '/settings.php';
