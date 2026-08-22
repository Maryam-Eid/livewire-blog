<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('posts:publish-scheduled')]
#[Description('Publish posts whose scheduled publication time has arrived')]
class PublishScheduledPosts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $publishedCount = 0;

        Post::query()
            ->dueForPublishing()
            ->eachById(function (Post $post) use (&$publishedCount): void {
                $post->update(['status' => 'published']);
                $publishedCount++;
            });

        $this->info("Published {$publishedCount} scheduled post(s).");

        return self::SUCCESS;
    }
}
