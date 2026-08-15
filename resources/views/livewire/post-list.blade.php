<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Latest Posts</h1>
            <p class="mt-2 text-lg text-gray-600">Thoughts, ideas, and stories from our team</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <!-- Search -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search posts..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
                </div>

                <!-- Categories -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Categories</h3>
                    <div class="space-y-2">
                        <button wire:click="$set('selectedCategory', '')"
                                class="w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedCategory === '' ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            All Categories
                        </button>
                        @foreach($categories as $category)
                            <button wire:click="$set('selectedCategory', '{{ $category->slug }}')"
                                    class="w-full text-left px-3 py-2 rounded-md text-sm flex items-center justify-between {{ $selectedCategory === $category->slug ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                <span class="flex items-center">
                                    <span class="inline-block w-3 h-3 rounded-full mr-2"
                                          style="background-color: {{ $category->color }}"></span>
                                    {{ $category->name }}
                                </span>
                                <span class="text-xs text-gray-500">({{ $category->posts_count }})</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Tags -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            @if($tag->posts_count > 0)
                                <button wire:click="$set('selectedTag', '{{ $tag->slug }}')"
                                        class="px-3 py-1 rounded-full text-xs font-medium {{ $selectedTag === $tag->slug ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                    {{ $tag->name }} ({{ $tag->posts_count }})
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Clear Filters -->
                @if($search || $selectedCategory || $selectedTag)
                    <button wire:click="clearFilters"
                            class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300">
                        Clear Filters
                    </button>
                @endif
            </aside>

            <div class="lg:col-span-3">
                <!-- Posts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @forelse($posts as $post)
                        <article wire:key="post-{{ $post->id }}"
                                 class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-200">
                            @if($post->featured_image)
                                <a href="{{ route('blog.show', $post->slug) }}" wire:navigate>
                                    <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                         class="w-full h-48 object-cover">
                                </a>
                            @else
                                <a href="{{ route('blog.show', $post->slug) }}" wire:navigate>
                                    <div
                                        class="w-full h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                        <span
                                            class="text-4xl text-white font-bold">{{ substr($post->title, 0, 1) }}</span>
                                    </div>
                                </a>
                            @endif

                            <div class="p-6">
                                <div class="flex items-center text-sm text-gray-500 mb-3">
                                    <span>{{ $post->published_at->format('M d, Y') }}</span>
                                    <span class="mx-2">•</span>
                                    <span>{{ $post->user->name }}</span>
                                    @if ($post->views_count > 0)
                                        <span>•</span>
                                        <span>{{ number_format($post->views_count) }} {{ Str::plural('view', $post->views_count) }}</span>
                                    @endif
                                </div>

                                <h2 class="text-xl font-bold text-gray-900 mb-2">
                                    <a href="{{ route('blog.show', $post->slug) }}" wire:navigate
                                       class="hover:text-indigo-600">
                                        {{ $post->title }}
                                    </a>
                                </h2>

                                @if($post->excerpt)
                                    <p class="text-gray-600 text-sm mb-4">
                                        {{ Str::limit($post->excerpt, 120) }}
                                    </p>
                                @endif

                                <a href="{{ route('blog.show', $post->slug) }}" wire:navigate
                                   class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
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
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $posts->links() }}
        </div>
    </div>

    <!-- Subscribe Section -->
    <div class="mt-20">
        <livewire:blog.subscribe />
    </div>
</div>
