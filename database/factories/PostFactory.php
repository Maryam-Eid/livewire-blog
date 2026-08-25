<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
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
        $title = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'excerpt' => fake()->paragraph(),
            'content' => collect(fake()->paragraphs(10))
                ->map(function (string $paragraph, int $index): string {
                    return match ($index) {
                        1 => '<p><strong>'.e($paragraph).'</strong></p>',
                        3 => '<h2>'.e(fake()->sentence()).'</h2><p>'.e($paragraph).'</p>',
                        5 => '<p>'.e($paragraph).' <a href="'.e(fake()->url()).'" target="_blank">Read more</a></p>',
                        7 => '<blockquote>'.e($paragraph).'</blockquote>',
                        default => '<p>'.e($paragraph).'</p>',
                    };
                })
                ->implode(''),
            'featured_image' => fake()->optional(0.8)->passthrough(
                'https://picsum.photos/seed/'.fake()->unique()->bothify('????-####').'/1200/630'
            ),
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
