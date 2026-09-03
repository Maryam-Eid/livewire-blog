<?php

use App\Models\Post;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';

    #[Url(as: 'access')]
    public string $accessFilter = 'all';

    public function with(): array
    {
        $query = Post::with(['user', 'categories', 'tags'])
            ->withCount('comments')
            ->latest();

        if ($this->search !== '') {
            $query->where(function ($searchQuery): void {
                $searchQuery
                    ->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('content', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->accessFilter === 'free') {
            $query->where('is_premium', false);
        }

        if ($this->accessFilter === 'premium') {
            $query->where('is_premium', true);
        }

        if (auth()->user()->hasRole('author')) {
            $query->where('user_id', auth()->id());
        }

        return [
            'posts' => $query->paginate(10),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingAccessFilter(): void
    {
        $this->resetPage();
    }

    public function deletePost(Post $post): void
    {
        if ((auth()->user()->can('delete-post') && $post->user_id == auth()->user()->id)
            || auth()->user()->can('delete-any-post')) {
            $post->delete();

            session()->flash('success', 'Post deleted successfully!');
        }
    }
};
?>


<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Posts</h1>
        <p class="mt-1 text-sm text-gray-600">Manage your blog posts</p>
    </div>

    {{-- filters --}}
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4 center">
        <div class="flex flex-col gap-4 sm:flex-row items-center">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search posts..."
                       class="p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
            </div>

            <div class="sm:w-48">
                <select wire:model.live="accessFilter"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All access</option>
                    <option value="free">Free</option>
                    <option value="premium">Premium</option>
                </select>
            </div>

            <div class="sm:w-48">
                <select wire:model.live="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All Posts</option>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            @can('create-post')
                <div>
                    <a href="{{ route('posts.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Post
                    </a>
                </div>
            @endcan
        </div>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" wire:transition>
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- posts table --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Title
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Categories
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Author
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($posts as $post)
                    <tr wire:key="post-{{ $post->id }}" wire:transition class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-900">
                                {{ $post->title }}
                                @if ($post->is_premium)
                                    <span class="premium-badge inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 px-2 py-0.5 text-xs font-semibold text-amber-950 shadow-sm shadow-amber-500/40">
                                        <x-premium-lock />
                                        Premium
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">{{ Str::limit($post->excerpt, 50) }}</div>
                            @if($post->comments_count > 0)
                                <div class="mt-1">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-600">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                      clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $post->comments_count }} {{ Str::plural('comment', $post->comments_count) }}
                                        </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-nowrap items-center gap-1">
                                @forelse($post->categories as $category)
                                    <span
                                        class="whitespace-nowrap px-2 py-1 text-xs font-semibold rounded-full text-white"
                                        style="background-color: {{ $category->color }}"
                                    >
                                            {{ $category->name }}
                                        </span>
                                @empty
                                    <span class="text-sm text-gray-400">No category</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $post->user->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $post->status === 'published' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $post->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $post->status === 'scheduled' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $post->status === 'archived' ? 'bg-gray-100 text-gray-800' : '' }}
                                ">
                                    {{ ucfirst($post->status) }}
                                </span>
                            @if ($post->status === 'scheduled')
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ $post->published_at?->format('M d, Y · g:i A') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $post->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-1">
                                @if ($post->status === 'published')
                                    <flux:tooltip content="View post" position="top">
                                        <a href="{{ route('blog.show', $post->slug) }}"
                                           target="_blank"
                                           class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">
                                            <flux:icon.arrow-top-right-on-square class="size-5" />
                                            <span class="sr-only">View</span>
                                        </a>
                                    </flux:tooltip>
                                @endif

                                @if(auth()->user()->can('edit-any-post') ||
                                    (auth()->user()->can('edit-post') && $post->user_id === auth()->id()))
                                    <flux:tooltip content="Edit post" position="top">
                                        <a href="{{ route('posts.edit', $post) }}"
                                           class="inline-flex size-8 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800">
                                            <flux:icon.pencil-square class="size-5" />
                                            <span class="sr-only">Edit</span>
                                        </a>
                                    </flux:tooltip>
                                @endif

                                @if(auth()->user()->can('delete-any-post') ||
                                    (auth()->user()->can('delete-post') && $post->user_id === auth()->id()))
                                    <flux:tooltip content="Delete post" position="top">
                                        <button
                                            type="button"
                                            wire:click="deletePost({{ $post->id }})"
                                            wire:confirm="Are you sure you want to delete this post?"
                                            class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                        >
                                            <flux:icon.trash class="size-5" />
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </flux:tooltip>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            No post found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- pagination --}}
    <div class="mt-6">
        {{ $posts->links() }}
    </div>
</div>
