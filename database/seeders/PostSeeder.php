<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\PostCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory()->count(5)->create();

        $categories = Category::all();
        $tags = Tag::all()->keyBy('slug');

        foreach (PostCatalog::data()['posts'] as $index => $definition) {
            $factory = Post::factory()->recycle($users);

            if (str_starts_with($definition['title'], 'Draft:')) {
                $factory = $factory->draft();
            } elseif (str_starts_with($definition['title'], 'Archived:')) {
                $factory = $factory->archived();
            } else {
                $factory = $factory->published();
            }

            $post = $factory->create([
                'title' => $definition['title'],
                'slug' => Str::slug($definition['title']).'-'.fake()->unique()->numerify('####'),
                'excerpt' => $definition['excerpt'],
                'content' => PostCatalog::buildContent($definition['blocks']),
                'featured_image' => PostCatalog::featuredImageForIndex($index),
            ]);

            $post->categories()->attach(
                $categories
                    ->whereIn('slug', $definition['categories'])
                    ->pluck('id')
                    ->all()
            );

            $post->tags()->attach(
                collect($definition['tags'])
                    ->map(fn (string $slug): ?int => $tags->get($slug)?->id)
                    ->filter()
                    ->all()
            );
        }
    }
}
