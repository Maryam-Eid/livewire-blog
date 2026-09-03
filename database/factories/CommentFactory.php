<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Support\CommentCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory()->published(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => CommentCatalog::random('approved'),
            'status' => 'approved',
        ];
    }

    /**
     * Indicate that the comment is awaiting moderation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'content' => CommentCatalog::random('pending'),
        ]);
    }

    /**
     * Indicate that the comment is spam.
     */
    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'spam',
            'content' => CommentCatalog::random('spam'),
        ]);
    }

    /**
     * Indicate that the comment is a reply.
     */
    public function replyTo(Comment $comment): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
            'content' => CommentCatalog::random('replies'),
        ]);
    }
}
