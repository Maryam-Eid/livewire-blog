<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Latest Posts</h1>
        <p class="mt-2 text-lg text-gray-600">Thoughts, ideas, and stories from our team</p>
    </div>

    <!-- Search -->
    <div class="mb-8">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search posts..."
            class="w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
    </div>

    <!-- Posts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($posts as $post)
            <article wire:key="post-{{ $post->id }}" class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-200">
                @if($post->featured_image)
                    <a href="{{ route('blog.show', $post->slug) }}" wire:navigate>
                        <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-48 object-cover">
                    </a>
                @else
                    <a href="{{ route('blog.show', $post->slug) }}" wire:navigate>
                        <div class="w-full h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <span class="text-4xl text-white font-bold">{{ substr($post->title, 0, 1) }}</span>
                        </div>
                    </a>
                @endif

                <div class="p-6">
                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <span>{{ $post->published_at->format('M d, Y') }}</span>
                        <span class="mx-2">•</span>
                        <span>{{ $post->user->name }}</span>
                    </div>

                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        <a href="{{ route('blog.show', $post->slug) }}" wire:navigate class="hover:text-indigo-600">
                            {{ $post->title }}
                        </a>
                    </h2>

                    @if($post->excerpt)
                        <p class="text-gray-600 text-sm mb-4">
                            {{ Str::limit($post->excerpt, 120) }}
                        </p>
                    @endif

                    <a href="{{ route('blog.show', $post->slug) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        Read more →
                    </a>
                </div>
            </article>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500">No posts found.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $posts->links() }}
    </div>
</div>
