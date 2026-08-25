<?php

use App\Livewire\PostList;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;

test('the blog can filter published posts by free and premium access', function () {
    $freePost = Post::factory()->published()->create([
        'title' => 'A free Sunday essay',
        'is_premium' => false,
    ]);

    $premiumPost = Post::factory()->published()->premium()->create([
        'title' => 'A locked premium briefing',
        'is_premium' => true,
    ]);

    Livewire::test(PostList::class)
        ->assertSee($freePost->title)
        ->assertSee($premiumPost->title)
        ->call('selectAccess', 'free')
        ->assertSet('accessFilter', 'free')
        ->assertSee($freePost->title)
        ->assertDontSee($premiumPost->title)
        ->call('selectAccess', 'premium')
        ->assertSet('accessFilter', 'premium')
        ->assertSee($premiumPost->title)
        ->assertDontSee($freePost->title);
});

test('premium posts show the lock badge to guests', function () {
    Post::factory()->published()->premium()->create([
        'title' => 'Members only travel notes',
        'is_premium' => true,
    ]);

    Livewire::test(PostList::class)
        ->assertSee('premium-badge', false)
        ->assertSee('premium-lock', false)
        ->assertDontSee('components.premium-lock');
});

test('guests cannot read premium post content', function () {
    $post = Post::factory()->published()->premium()->create([
        'title' => 'Hidden lighthouse diary',
        'content' => '<p>Secret lighthouse paragraph.</p>',
        'is_premium' => true,
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Premium article')
        ->assertSee('premium-lock', false)
        ->assertDontSee('Secret lighthouse paragraph.');
});

test('premium subscribers can read premium post content', function () {
    $post = Post::factory()->published()->premium()->create([
        'title' => 'Hidden lighthouse diary',
        'content' => '<p>Secret lighthouse paragraph.</p>',
        'is_premium' => true,
    ]);

    $subscriber = User::factory()->create();
    createPremiumSubscription($subscriber);

    $this->actingAs($subscriber)
        ->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Secret lighthouse paragraph.')
        ->assertDontSee('Premium article');
});

test('staff members can read premium post content without a subscription', function () {
    $post = Post::factory()->published()->premium()->create([
        'title' => 'Hidden lighthouse diary',
        'content' => '<p>Secret lighthouse paragraph.</p>',
        'is_premium' => true,
    ]);

    $this->actingAs(staffUser())
        ->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Secret lighthouse paragraph.');
});
