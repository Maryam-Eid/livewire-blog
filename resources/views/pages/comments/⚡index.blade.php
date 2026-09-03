<?php

use App\Models\Comment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    public function with(): array
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('editor');

        $query = Comment::query()
            ->with(['user', 'post'])
            ->latest();

        if (! $isAdmin) {
            $query->whereHas('post', fn ($postQuery) => $postQuery->where('user_id', $user->id));
        }

        if ($this->search !== '') {
            $query->where(function ($searchQuery): void {
                $searchQuery
                    ->where('content', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'));
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return [
            'comments' => $query->paginate(20),
            'isAdmin' => $isAdmin,
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approveComment(Comment $comment): void
    {
        $this->authorizeComment($comment);
        $comment->update(['status' => 'approved']);
        session()->flash('success', 'Comment approved!');
    }

    public function markAsSpam(Comment $comment): void
    {
        $this->authorizeComment($comment);
        $comment->update(['status' => 'spam']);
        session()->flash('success', 'Comment marked as spam!');
    }

    public function deleteComment(Comment $comment): void
    {
        $this->authorizeComment($comment);
        $comment->delete();
        session()->flash('success', 'Comment deleted!');
    }

    private function authorizeComment(Comment $comment): void
    {
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('editor')) {
            return;
        }

        abort_unless($comment->post?->user_id === $user->id, 403);
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Comments</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $isAdmin ? 'Moderate and manage post comments' : 'Comments on your posts' }}
        </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4 items-center">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search comments..."
                    class="p-2  w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="sm:w-48">
                <select
                    wire:model.live="statusFilter"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="all">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="spam">Spam</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" wire:transition>
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Comments List -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="divide-y divide-gray-200">
            @forelse($comments as $comment)
                <div class="p-6 hover:bg-gray-50" wire:key="comment-{{ $comment->id }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <img
                                src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&background=4f46e5&color=fff"
                                alt="{{ $comment->user->name }}"
                                class="w-10 h-10 rounded-full mr-3"
                            >
                            <div>
                                <p class="font-medium text-gray-900">{{ $comment->user->name }}</p>
                                <p class="text-sm text-gray-500">
                                    on <a href="{{ route('blog.show', $comment->post->slug) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">{{ Str::limit($comment->post->title, 40) }}</a>
                                </p>
                            </div>
                        </div>

                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $comment->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $comment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $comment->status === 'spam' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ ucfirst($comment->status) }}
                        </span>
                    </div>

                    <div class="text-gray-700 mb-3">
                        {{ $comment->content }}
                    </div>

                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            {{ $comment->created_at->format('M d, Y \a\t g:i A') }}
                        </p>

                        <div class="flex items-center gap-1">
                            @if($comment->status !== 'approved')
                                <flux:tooltip content="Approve comment" position="top">
                                    <button
                                        type="button"
                                        wire:click="approveComment({{ $comment->id }})"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-green-600 transition hover:bg-green-50 hover:text-green-800"
                                    >
                                        <flux:icon.check class="size-5" />
                                        <span class="sr-only">Approve</span>
                                    </button>
                                </flux:tooltip>
                            @endif

                            @if($comment->status !== 'spam')
                                <flux:tooltip content="Mark as spam" position="top">
                                    <button
                                        type="button"
                                        wire:click="markAsSpam({{ $comment->id }})"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-amber-600 transition hover:bg-amber-50 hover:text-amber-800"
                                    >
                                        <flux:icon.no-symbol class="size-5" />
                                        <span class="sr-only">Mark as Spam</span>
                                    </button>
                                </flux:tooltip>
                            @endif

                            <flux:tooltip content="View post" position="top">
                                <a
                                    href="{{ route('blog.show', $comment->post->slug) }}"
                                    target="_blank"
                                    class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                >
                                    <flux:icon.arrow-top-right-on-square class="size-5" />
                                    <span class="sr-only">View</span>
                                </a>
                            </flux:tooltip>

                            <flux:tooltip content="Delete comment" position="top">
                                <button
                                    type="button"
                                    wire:click="deleteComment({{ $comment->id }})"
                                    wire:confirm="Are you sure you want to delete this comment?"
                                    class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                >
                                    <flux:icon.trash class="size-5" />
                                    <span class="sr-only">Delete</span>
                                </button>
                            </flux:tooltip>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500">
                    No comments found.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $comments->links() }}
    </div>
</div>
