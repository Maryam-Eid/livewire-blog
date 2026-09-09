<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:api-register');

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');

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

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('can:manage-roles')
        ->name('api.categories.store');

    Route::patch('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('can:manage-roles')
        ->name('api.categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('can:manage-roles')
        ->name('api.categories.destroy');
});

Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.posts.show');
