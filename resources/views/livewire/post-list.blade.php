<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Latest Articles</h1>
            <p class="mt-2 text-lg text-gray-600">Thoughts, ideas, and stories from our team</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <!-- Search -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search articles..."
                           class="p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
                </div>

                <!-- Access -->
                <div class="mb-6">
                    <h3 class="mb-3 text-sm font-medium text-gray-700">Access</h3>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['all' => 'All', 'free' => 'Free', 'premium' => 'Premium'] as $access => $label)
                            <button
                                type="button"
                                wire:key="access-{{ $access }}"
                                wire:click="selectAccess('{{ $access }}')"
                                @class([
                                    'cursor-pointer rounded-md px-2 py-2 text-xs font-semibold transition',
                                    'premium-badge bg-gradient-to-r from-amber-200 via-yellow-100 to-amber-300 text-amber-800 shadow-sm shadow-amber-400/40' => $access === 'premium' && $accessFilter !== 'premium',
                                    'premium-badge bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 text-amber-950 shadow-sm shadow-amber-500/40' => $access === 'premium' && $accessFilter === 'premium',
                                    'bg-indigo-600 text-white' => $access !== 'premium' && $accessFilter === $access,
                                    'bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700' => $access !== 'premium' && $accessFilter !== $access,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Categories -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Categories</h3>
                    <div class="space-y-2">
                        <button wire:click="selectCategory"
                                class="cursor-pointer hover:bg-indigo-50 w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedCategory === '' ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            All Categories
                        </button>
                        @foreach($categories as $category)
                            <button wire:click="selectCategory('{{ $category->slug }}')"
                                    class="cursor-pointer hover:bg-indigo-50 w-full text-left px-3 py-2 rounded-md text-sm flex items-center justify-between {{ $selectedCategory === $category->slug ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
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
                                <button wire:click="selectTag('{{ $tag->slug }}')"
                                        class="cursor-pointer px-3 py-1 rounded-full text-xs font-medium {{ $selectedTag === $tag->slug ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                    {{ $tag->name }} ({{ $tag->posts_count }})
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Clear Filters -->
                @if($search || $selectedCategory || $selectedTag || $accessFilter !== 'all')
                    <button wire:click="clearFilters"
                            class="cursor-pointer w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300">
                        Clear Filters
                    </button>
                @endif
            </aside>

            <div class="lg:col-span-3">
                <!-- Posts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @forelse($posts as $post)
                        <article wire:key="post-{{ $post->id }}"
                                 class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-100 {{ $post->is_premium && ! $hasPremiumAccess ? 'ring-1 ring-violet-200' : '' }}">

                            <!-- Image -->
                            <a href="{{ route('blog.show', $post->slug) }}" wire:navigate class="block relative overflow-hidden">
                                @if($post->featured_image)
                                    <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}"
                                         loading="lazy"
                                         class="h-52 w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    <div hidden class="flex h-52 w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 transition-transform duration-500 group-hover:scale-105">
                                        <span class="px-6 text-center text-xl font-bold leading-snug text-white/90">{{ $post->title }}</span>
                                    </div>
                                @else
                                    <div class="flex h-52 w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 transition-transform duration-500 group-hover:scale-105">
                                        <span class="px-6 text-center text-xl font-bold leading-snug text-white/90">{{ $post->title }}</span>
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                                @if($category = $post->categories->first())
                                    <span class="absolute top-3 left-3 inline-flex items-center rounded-full bg-white/90 backdrop-blur px-3 py-1 text-xs font-semibold text-indigo-700 shadow-sm">
                    {{ $category->name }}
                </span>
                                @endif

                                @if ($post->is_premium)
                                    <span class="absolute top-3 right-3">
                                        <span class="premium-badge inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 px-3 py-1 text-xs font-semibold text-amber-950 shadow-sm shadow-amber-500/40">
                                            @unless ($hasPremiumAccess)
                                                <x-premium-lock />
                                            @endunless
                                            Premium
                                        </span>
                                    </span>
                                @endif
                            </a>

                            <!-- Content -->
                            <div class="flex flex-1 flex-col p-6">
                                <h2 class="mb-2 text-xl font-bold leading-snug text-gray-900">
                                    <a href="{{ route('blog.show', $post->slug) }}" wire:navigate
                                       class="transition-colors hover:text-indigo-600">
                                        {{ $post->title }}
                                    </a>
                                </h2>

                                @if($post->excerpt)
                                    <p class="mb-4 flex-1 text-sm leading-relaxed text-gray-600 line-clamp-3">
                                        {{ Str::limit($post->excerpt, 120) }}
                                    </p>
                                @endif

                                <!-- Author + Meta -->
                                <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-4">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($post->user->name) }}&background=4f46e5&color=fff"
                                             alt="{{ $post->user->name }}"
                                             class="h-8 w-8 shrink-0 rounded-full">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-900">{{ $post->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $post->published_at->format('M d, Y') }}</p>
                                        </div>
                                    </div>

                                        <span class="flex shrink-0 items-center gap-1 text-xs text-gray-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        {{ number_format($post->views_count) }}
                    </span>
                                </div>

                                <a href="{{ route('blog.show', $post->slug) }}" wire:navigate
                                   class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 transition-all group-hover:gap-2 hover:text-indigo-800">
                                    Read more
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 py-16">
                            <svg class="mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
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
