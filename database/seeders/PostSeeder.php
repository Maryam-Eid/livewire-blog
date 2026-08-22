<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as SupportCollection;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory()->count(5)->create();

        $categories = Category::all();
        $tags = Tag::all();

        $this->createPosts(40, 'published', $users, $categories, $tags);
        $this->createPosts(10, 'draft', $users, $categories, $tags);
        $this->createPosts(10, 'archived', $users, $categories, $tags);
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Tag>  $tags
     */
    private function createPosts(
        int $count,
        string $state,
        Collection $users,
        Collection $categories,
        Collection $tags,
    ): void {
        Post::factory()
            ->count($count)
            ->{$state}()
            ->recycle($users)
            ->create()
            ->each(function (Post $post) use ($categories, $tags): void {
                $this->attachRandomRelations($post, $categories, $tags);
            });
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Tag>  $tags
     */
    private function attachRandomRelations(Post $post, Collection $categories, Collection $tags): void
    {
        if ($categories->isNotEmpty()) {
            $post->categories()->attach(
                $this->randomIds($categories, fake()->numberBetween(1, min(2, $categories->count())))
            );
        }

        if ($tags->isNotEmpty()) {
            $post->tags()->attach(
                $this->randomIds($tags, fake()->numberBetween(1, min(4, $tags->count())))
            );
        }
    }

    /**
     * @param  Collection<int, Category>|Collection<int, Tag>  $models
     * @return array<int, int>
     */
    private function randomIds(Collection $models, int $count): array
    {
        /** @var SupportCollection<int, Category|Tag> $selected */
        $selected = $models->random($count);

        return $selected->pluck('id')->all();
    }
}
