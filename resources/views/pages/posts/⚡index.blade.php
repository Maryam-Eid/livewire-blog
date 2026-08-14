<?php

use App\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';

    public function with(): array
    {
        $query = Post::with(['user','categories','tags'])
            ->withCount('comments')
            ->latest();

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '$')
                ->orWhere('content', 'like', '%' . $this->search . '$');
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
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

    public function updatingPost(): void
    {
        $this->resetPage();
    }

    public function deletePost(Post $post): void
    {
        if ((auth()->user()->can('delete-post') && $post->user_id == auth()->user()->id)
            || auth()->user()->can('delete-any-post')){
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
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search posts..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="sm:w-48">
                <select wire:model.live="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All Posts</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            @can('create-post')
                <div>
                    <a href="{{ route('posts.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
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
                            <div class="text-sm font-medium text-gray-900">{{ $post->title }}</div>
                            <div class="text-sm text-gray-500">{{ Str::limit($post->excerpt, 50) }}</div>
                            @if($post->comments_count > 0)
                                <div class="mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $post->comments_count }} {{ Str::plural('comment', $post->comments_count) }}
                                        </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($post->categories as $category)
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full text-white"
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
                                    {{ $post->status === 'archived' ? 'bg-gray-100 text-gray-800' : '' }}
                                ">
                                    {{ ucfirst($post->status) }}
                                </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $post->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                @if(auth()->user()->can('edit-any-post') ||
                                    (auth()->user()->can('edit-post') && $post->user_id === auth()->id()))
                                    <a href="{{ route('posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>
                                @endif

                                @if(auth()->user()->can('delete-any-post') ||
                                    (auth()->user()->can('delete-post') && $post->user_id === auth()->id()))
                                    <button
                                        wire:click="deletePost({{ $post->id }})"
                                        wire:confirm="Are you sure you want to delete this post?"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
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
