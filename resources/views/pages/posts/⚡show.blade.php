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
            ->published()
            ->with(['user', 'categories', 'tags'])
            ->firstOrFail();

        $this->trackView();
    }

    public function with(): array
    {
        return [
            'canReadPost' => ! $this->post->is_premium
                || (auth()->user()?->hasPremiumAccess() ?? false),
        ];
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
            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}"
                 loading="lazy"
                 class="w-full h-96 object-cover rounded-lg mb-8"
                 onerror="this.remove()">
        @endif

        <!-- Post Header -->
        <header class="mb-8">
            @if ($post->is_premium)
                <span class="premium-badge mb-3 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 px-3 py-1 text-sm font-semibold text-amber-950 shadow-sm shadow-amber-500/40">
                    @unless ($canReadPost)
                        <x-premium-lock />
                    @endunless
                    Premium
                </span>
            @endif
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


        @if ($canReadPost)
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
        @else
            <section class="overflow-hidden rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-indigo-50 p-8 text-center shadow-sm sm:p-12">
                <span class="premium-badge inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 px-3 py-1 text-sm font-semibold text-amber-950 shadow-sm shadow-amber-500/40">
                    <x-premium-lock />
                    Premium article
                </span>
                <h2 class="mt-5 text-3xl font-bold text-gray-950">Continue reading with Premium</h2>
                @if (filled($post->excerpt))
                    <p class="mx-auto mt-4 max-w-2xl text-lg leading-8 text-gray-600">{{ $post->excerpt }}</p>
                @endif
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('pricing') }}" wire:navigate class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700">
                        View Premium plans
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-gray-700 hover:bg-gray-50">
                            Log in
                        </a>
                    @endguest
                </div>
            </section>
        @endif

    </article>

    <!-- Subscribe Section -->
    <div class="mt-20">
        <livewire:blog.subscribe />
    </div>
</div>
