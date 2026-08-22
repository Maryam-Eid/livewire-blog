<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        $posts = Post::query()
            ->where('status', 'published')
            ->get();

        if ($posts->isEmpty()) {
            $posts = Post::factory()
                ->count(10)
                ->published()
                ->recycle($users)
                ->create();
        }

        $approvedComments = Comment::factory()
            ->count(150)
            ->recycle($posts)
            ->recycle($users)
            ->create();

        Comment::factory()
            ->count(15)
            ->pending()
            ->recycle($posts)
            ->recycle($users)
            ->create();

        Comment::factory()
            ->count(5)
            ->spam()
            ->recycle($posts)
            ->recycle($users)
            ->create();

        $approvedComments
            ->random(30)
            ->each(function (Comment $comment) use ($users): void {
                Comment::factory()
                    ->replyTo($comment)
                    ->recycle($users)
                    ->create();
            });
    }
}
