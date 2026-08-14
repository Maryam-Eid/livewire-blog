<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'Laravel',
            'PHP',
            'JavaScript',
            'Vue.js',
            'React',
            'Livewire',
            'Tailwind CSS',
            'Web Development',
            'Mobile Apps',
            'AI & ML',
            'Productivity',
            'Remote Work',
            'Entrepreneurship',
            'Marketing',
            'Design',
        ];

        foreach ($tags as $tag) {
            Tag::create(['name' => $tag]);
        }
    }
}
