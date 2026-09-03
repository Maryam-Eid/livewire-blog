<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Support\PostCatalog;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $catalogPost = PostCatalog::data()['posts'][fake()->numberBetween(0, 59)];
        $title = $catalogPost['title'];

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'excerpt' => $catalogPost['excerpt'],
            'content' => PostCatalog::buildContent($catalogPost['blocks']),
            'featured_image' => PostCatalog::featuredImageForIndex(fake()->numberBetween(0, 19)),
            'status' => 'draft',
            'is_premium' => fake()->boolean(),
            'published_at' => null,
            'views_count' => rand(500, 2000),
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $attributes['published_at'] ?? now(),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn () => [
            'is_premium' => true,
        ]);
    }

    /**
     * Indicate that the post is scheduled for future publication.
     */
    public function scheduled(?DateTimeInterface $publishAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'published_at' => $publishAt ?? fake()->dateTimeBetween('+1 hour', '+1 month'),
        ]);
    }

    /**
     * Indicate that the post is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'published_at' => $attributes['published_at'] ?? fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
