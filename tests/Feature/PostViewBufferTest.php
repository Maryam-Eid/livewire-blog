<?php

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;

test('a published post view is stored in the database when buffered views are flushed', function () {
    $reader = User::factory()->create();
    $post = Post::factory()->published()->create([
        'views_count' => 3,
    ]);

    $this->actingAs($reader)
        ->get(route('blog.show', $post->slug))
        ->assertOk();

    expect(PostView::query()->count())->toBe(0)
        ->and($post->refresh()->views_count)->toBe(3);

    $this->artisan('posts:flush-views')->assertSuccessful();

    $view = PostView::query()->sole();

    expect($view->post_id)->toBe($post->id)
        ->and($view->user_id)->toBe($reader->id)
        ->and($post->refresh()->views_count)->toBe(4);

    $this->artisan('posts:flush-views')->assertSuccessful();

    expect(PostView::query()->count())->toBe(1)
        ->and($post->refresh()->views_count)->toBe(4);
});

test('the api buffers a guest view until it is flushed', function () {
    $post = Post::factory()->published()->create([
        'views_count' => 0,
    ]);

    $this->getJson(route('api.posts.show', $post))->assertOk();

    expect(PostView::query()->count())->toBe(0)
        ->and($post->refresh()->views_count)->toBe(0);

    $this->artisan('posts:flush-views')->assertSuccessful();

    expect(PostView::query()->sole()->user_id)->toBeNull()
        ->and($post->refresh()->views_count)->toBe(1);
});

test('flush drops buffered views whose post was deleted', function () {
    $post = Post::factory()->published()->create([
        'views_count' => 1,
    ]);

    $this->get(route('blog.show', $post->slug))->assertOk();

    $post->delete();

    $this->artisan('posts:flush-views')->assertSuccessful();

    expect(PostView::query()->count())->toBe(0);
});
