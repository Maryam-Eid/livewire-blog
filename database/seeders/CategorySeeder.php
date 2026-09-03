<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\PostCatalog;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PostCatalog::data()['categories'] as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'color' => $category['color'],
                ],
            );
        }
    }
}
