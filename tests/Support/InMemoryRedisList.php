<?php

namespace Tests\Support;

class InMemoryRedisList
{
    /** @var array<string, list<string>> */
    public array $lists = [];

    public function rpush(string $key, string $value): int
    {
        $this->lists[$key][] = $value;

        return count($this->lists[$key]);
    }

    /**
     * @return list<string>
     */
    public function lrange(string $key, int $start, int $end): array
    {
        $items = $this->lists[$key] ?? [];
        $length = count($items);

        if ($length === 0) {
            return [];
        }

        [$start, $end] = $this->bounds($start, $end, $length);

        if ($start > $end || $start >= $length) {
            return [];
        }

        return array_values(array_slice($items, $start, $end - $start + 1));
    }

    public function ltrim(string $key, int $start, int $end): bool
    {
        $items = $this->lists[$key] ?? [];
        $length = count($items);

        if ($length === 0) {
            $this->lists[$key] = [];

            return true;
        }

        [$start, $end] = $this->bounds($start, $end, $length);

        if ($start > $end || $start >= $length) {
            $this->lists[$key] = [];

            return true;
        }

        $this->lists[$key] = array_values(array_slice($items, $start, $end - $start + 1));

        return true;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function bounds(int $start, int $end, int $length): array
    {
        if ($start < 0) {
            $start = $length + $start;
        }

        if ($end < 0) {
            $end = $length + $end;
        }

        return [$start, $end];
    }
}
