<?php

use App\Models\User;
use App\Support\UserHome;

test('the public home page redirects to the blog', function () {
    $this->get(route('home'))
        ->assertRedirect('/blog');
});

test('readers cannot open the staff dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('staff members can open the dashboard', function () {
    $this->actingAs(staffUser())
        ->get(route('dashboard'))
        ->assertOk();
});

test('readers are sent to the blog after authentication', function () {
    $user = User::factory()->create();

    expect(app(UserHome::class)->path($user))->toBe(route('blog.index', absolute: false));
});

test('staff members are sent to the dashboard after authentication', function () {
    $user = staffUser();

    expect(app(UserHome::class)->path($user))->toBe(route('dashboard', absolute: false));
});

test('readers can open account billing settings', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('billing.edit'))
        ->assertOk()
        ->assertSee('Premium membership');
});

test('staff members cannot open account billing settings', function () {
    $this->actingAs(staffUser())
        ->get(route('billing.edit'))
        ->assertForbidden();
});

test('readers cannot open staff-only post administration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('posts.index'))
        ->assertForbidden();
});
