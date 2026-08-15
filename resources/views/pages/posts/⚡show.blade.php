<?php

use App\Models\PostView;
use Livewire\Component;
use App\Models\Post;
use Livewire\Attributes\Layout;

new #[Layout('layouts.public')]
class extends Component {
    public Post $post;

    public function mount($slug)
    {
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with(['user', 'categories', 'tags'])
            ->firstOrFail();

        $this->trackView();
    }

    protected function trackView()
    {
        $this->post->increment('views_count');

        PostView::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now(),
        ]);
    }
};
?>

<div>
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back link -->
        <div class="mb-6">
            <a href="{{ route('blog.index') }}" wire:navigate
               class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                ← Back to posts
            </a>
        </div>

        <!-- Featured Image -->
        @if($post->featured_image)
            <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                 class="w-full h-96 object-cover rounded-lg mb-8">
        @endif

        <!-- Post Header -->
        <header class="mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                {{ $post->title }}
            </h1>

            <div class="flex items-center text-gray-600">
                <img
                    src="https://ui-avatars.com/api/?name={{ urlencode($post->user->name) }}&background=4f46e5&color=fff"
                    alt="{{ $post->user->name }}" class="w-10 h-10 rounded-full mr-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $post->user->name }}</p>
                    <p class="text-sm">{{ $post->published_at->format('F d, Y') }}
                        • {{ ceil(str_word_count(strip_tags($post->content)) / 200) }} min read
                        • {{ number_format($post->views_count) }} views</p>
                </div>
            </div>

            <!-- Categories and Tags -->
            <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-gray-200">
                <!-- Categories -->
                @if($post->categories->count() > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500">Categories:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($post->categories as $category)
                                <a
                                    href="{{ route('blog.index', ['category' => $category->slug]) }}"
                                    wire:navigate
                                    class="px-3 py-1 text-sm font-semibold rounded-full text-white hover:opacity-80 transition"
                                    style="background-color: {{ $category->color }}"
                                >
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tags -->
                @if($post->tags->count() > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500">Tags:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($post->tags as $tag)
                                <a
                                    href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                                    wire:navigate
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
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
                    <img
                        src="https://ui-avatars.com/api/?name={{ urlencode($post->user->name) }}&background=4f46e5&color=fff"
                        alt="{{ $post->user->name }}" class="w-10 h-10 rounded-full mr-4">
                    <div>
                        <p class="font-medium text-gray-900">Written by {{ $post->user->name }}</p>
                        <p class="text-sm text-gray-600">Published on {{ $post->published_at->format('F d, Y') }}</p>
                    </div>
                </div>
            </div>
        </footer>

        {{-- Comment section --}}
        <livewire:blog.comments :post="$post"/>
    </article>
</div>
