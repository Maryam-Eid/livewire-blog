<?php

namespace App\Support;

use App\Models\Post;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PostPublication
{
    public static function publishedAt(string $status, ?string $scheduledAt = null, ?Post $existing = null): ?CarbonInterface
    {
        return match ($status) {
            'published' => $existing?->status === 'published' && $existing->published_at
                ? $existing->published_at
                : now(),
            'scheduled' => CarbonImmutable::parse($scheduledAt, config('app.timezone')),
            'draft' => null,
            default => $existing?->published_at,
        };
    }
}
