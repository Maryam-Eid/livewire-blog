<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\PostCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefreshPostContentSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = PostCatalog::data();

        DB::transaction(function () use ($catalog): void {
            $categories = $this->syncCategories($catalog['categories']);
            $tags = $this->syncTags($catalog['tags']);

            Post::query()
                ->orderBy('id')
                ->get()
                ->values()
                ->each(function (Post $post, int $index) use ($catalog, $categories, $tags): void {
                    $definition = $catalog['posts'][$index] ?? null;

                    if ($definition === null) {
                        return;
                    }

                    $slug = Str::slug($definition['title']).'-'.$post->id;

                    $post->update([
                        'title' => $definition['title'],
                        'slug' => $slug,
                        'excerpt' => $definition['excerpt'],
                        'content' => PostCatalog::buildContent($definition['blocks']),
                        'featured_image' => PostCatalog::featuredImageForIndex($index),
                    ]);

                    $post->categories()->sync(
                        $this->idsForSlugs($categories, $definition['categories'])
                    );

                    $post->tags()->sync(
                        $this->idsForSlugs($tags, $definition['tags'])
                    );
                });
        });
    }

    /**
     * @param  list<array{name: string, slug: string, description: string, color: string}>  $definitions
     * @return Collection<string, Category>
     */
    private function syncCategories(array $definitions): Collection
    {
        $categories = collect();

        foreach ($definitions as $definition) {
            $categories->put(
                $definition['slug'],
                Category::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'color' => $definition['color'],
                    ],
                ),
            );
        }

        Category::query()
            ->whereNotIn('slug', $categories->keys())
            ->get()
            ->each(function (Category $category): void {
                $category->posts()->detach();
                $category->delete();
            });

        return $categories;
    }

    /**
     * @param  list<array{name: string, slug: string}>  $definitions
     * @return Collection<string, Tag>
     */
    private function syncTags(array $definitions): Collection
    {
        $tags = collect();

        foreach ($definitions as $definition) {
            $tags->put(
                $definition['slug'],
                Tag::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    ['name' => $definition['name']],
                ),
            );
        }

        Tag::query()
            ->whereNotIn('slug', $tags->keys())
            ->get()
            ->each(function (Tag $tag): void {
                $tag->posts()->detach();
                $tag->delete();
            });

        return $tags;
    }

    /**
     * @param  Collection<string, Category|Tag>  $models
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function idsForSlugs(Collection $models, array $slugs): array
    {
        return collect($slugs)
            ->map(fn (string $slug): ?int => $models->get($slug)?->id)
            ->filter()
            ->values()
            ->all();
    }
}
