<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:api-register');

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
Route::get('/tags', [TagController::class, 'index'])->name('api.tags.index');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/posts/manage', [PostController::class, 'manage'])
        ->middleware('can:create-post')
        ->name('api.posts.manage');

    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('can:create-post')
        ->name('api.posts.store');

    Route::patch('/posts/{post}', [PostController::class, 'update'])
        ->name('api.posts.update');

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->name('api.posts.comments.store');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('can:manage-roles')
        ->name('api.categories.store');

    Route::patch('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('can:manage-roles')
        ->name('api.categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('can:manage-roles')
        ->name('api.categories.destroy');

    Route::post('/tags', [TagController::class, 'store'])
        ->middleware('can:manage-roles')
        ->name('api.tags.store');

    Route::patch('/tags/{tag}', [TagController::class, 'update'])
        ->middleware('can:manage-roles')
        ->name('api.tags.update');

    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])
        ->middleware('can:manage-roles')
        ->name('api.tags.destroy');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:manage-users')
        ->name('api.users.index');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('can:manage-users')
        ->name('api.users.store');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('can:manage-users')
        ->name('api.users.show');

    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->middleware('can:manage-users')
        ->name('api.users.update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:manage-users')
        ->name('api.users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('can:manage-users')
        ->name('api.roles.index');
});

Route::get('/posts/{post}/comments', [CommentController::class, 'index'])
    ->name('api.posts.comments.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.posts.show');
