<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

trait RefreshInMemoryDatabase
{
    use RefreshDatabase {
        refreshDatabase as refreshInMemoryDatabase;
    }

    public function refreshDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to refresh [{$connection}:{$database}]. Feature tests must use sqlite :memory:."
            );
        }

        $this->refreshInMemoryDatabase();
    }
}
