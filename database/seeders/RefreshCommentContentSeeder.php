<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Support\CommentCatalog;
use Illuminate\Database\Seeder;

class RefreshCommentContentSeeder extends Seeder
{
    public function run(): void
    {
        Comment::query()
            ->orderBy('id')
            ->get()
            ->each(function (Comment $comment): void {
                $type = match ($comment->status) {
                    'pending' => 'pending',
                    'spam' => 'spam',
                    default => $comment->parent_id ? 'replies' : 'approved',
                };

                $comment->update([
                    'content' => CommentCatalog::random($type),
                ]);
            });
    }
}
