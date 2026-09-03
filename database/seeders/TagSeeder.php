<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Support\PostCatalog;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PostCatalog::data()['tags'] as $tag) {
            Tag::query()->updateOrCreate(
                ['slug' => $tag['slug']],
                ['name' => $tag['name']],
            );
        }
    }
}
