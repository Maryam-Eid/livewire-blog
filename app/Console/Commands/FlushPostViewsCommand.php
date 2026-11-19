<?php

namespace App\Console\Commands;

use App\Actions\FlushPostViews;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('posts:flush-views')]
#[Description('Move buffered post views from Redis into the database')]
class FlushPostViewsCommand extends Command
{
    public function handle(FlushPostViews $flushPostViews): int
    {
        $stored = $flushPostViews();

        $this->info("Stored {$stored} post view(s).");

        return self::SUCCESS;
    }
}
