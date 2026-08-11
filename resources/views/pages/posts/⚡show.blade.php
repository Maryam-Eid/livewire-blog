<?php

use Livewire\Component;
use App\Models\Post;
use Livewire\Attributes\Layout;

new #[Layout('layouts.public')] class extends Component
{
    public Post $post;

    public function mount($slug)
    {
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with('user')
            ->firstOrFail();
    }
};
?>

<div>
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back link -->
        <div class="mb-6">
            <a href="{{ route('blog.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                ← Back to posts
            </a>
        </div>

        <!-- Featured Image -->
        @if($post->featured_image)
            <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-96 object-cover rounded-lg mb-8">
        @endif

        <!-- Post Header -->
        <header class="mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                {{ $post->title }}
            </h1>

            <div class="flex items-center text-gray-600">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($post->user->name) }}&background=4f46e5&color=fff" alt="{{ $post->user->name }}" class="w-10 h-10 rounded-full mr-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $post->user->name }}</p>
                    <p class="text-sm">{{ $post->published_at->format('F d, Y') }} • {{ ceil(str_word_count(strip_tags($post->content)) / 200) }} min read</p>
                </div>
            </div>
        </header>

        <!-- Post Content -->
        <div class="prose prose-lg prose-indigo max-w-none mb-12">
            {!! $post->content !!}
        </div>

        <!-- Post Footer -->
        <footer class="border-t border-gray-200 pt-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($post->user->name) }}&background=4f46e5&color=fff" alt="{{ $post->user->name }}" class="w-10 h-10 rounded-full mr-4">
                    <div>
                        <p class="font-medium text-gray-900">Written by {{ $post->user->name }}</p>
                        <p class="text-sm text-gray-600">Published on {{ $post->published_at->format('F d, Y') }}</p>
                    </div>
                </div>
            </div>
        </footer>
    </article>
</div>
