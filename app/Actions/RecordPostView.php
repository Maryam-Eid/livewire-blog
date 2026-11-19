<?php

namespace App\Actions;

use App\Models\Post;
use App\Support\PostViewQueue;

class RecordPostView
{
    public function __construct(private PostViewQueue $views) {}

    public function __invoke(Post $post, ?int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        $this->views->push((string) json_encode([
            'post_id' => $post->getKey(),
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => filled($userAgent) ? substr($userAgent, 0, 255) : null,
            'viewed_at' => now()->toDateTimeString(),
        ], JSON_THROW_ON_ERROR));
    }
}
