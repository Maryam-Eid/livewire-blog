<?php

use App\Jobs\DispatchNewsletter;
use App\Models\Newsletter;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('premium newsletters are queued only for premium subscribers', function () {
    $author = User::factory()->create();
    $freeMember = User::factory()->create();
    $premiumMember = User::factory()->create();

    createPremiumSubscription($premiumMember);

    Subscriber::query()->create([
        'user_id' => $freeMember->id,
        'email' => 'free-reader@example.com',
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    Subscriber::query()->create([
        'user_id' => $premiumMember->id,
        'email' => 'premium-reader@example.com',
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    $newsletter = Newsletter::query()->create([
        'user_id' => $author->id,
        'subject' => 'Members briefing',
        'content' => '<p>For premium readers only.</p>',
        'audience' => 'premium',
        'status' => 'draft',
    ]);

    Queue::fake();

    (new DispatchNewsletter($newsletter))->handle();

    $this->assertDatabaseHas('newsletter_deliveries', [
        'newsletter_id' => $newsletter->id,
        'email' => 'premium-reader@example.com',
    ]);

    $this->assertDatabaseMissing('newsletter_deliveries', [
        'newsletter_id' => $newsletter->id,
        'email' => 'free-reader@example.com',
    ]);
});
