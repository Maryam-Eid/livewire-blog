<?php

namespace App\Support;

interface PostViewQueue
{
    public function push(string $payload): void;

    /**
     * @return list<string>
     */
    public function claim(int $limit): array;

    public function acknowledge(int $count): void;
}
