<?php

namespace App\Support;

use Illuminate\Support\Facades\Redis;

class RedisPostViewQueue implements PostViewQueue
{
    private const KEY = 'post-views:pending';

    public function __construct(private readonly ?object $redis = null) {}

    public function push(string $payload): void
    {
        $this->connection()->rpush(self::KEY, $payload);
    }

    public function claim(int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $items = $this->connection()->lrange(self::KEY, 0, $limit - 1);

        if (! is_array($items)) {
            return [];
        }

        return array_values($items);
    }

    public function acknowledge(int $count): void
    {
        if ($count < 1) {
            return;
        }

        $this->connection()->ltrim(self::KEY, $count, -1);
    }

    private function connection(): object
    {
        return $this->redis ?? Redis::connection();
    }
}
