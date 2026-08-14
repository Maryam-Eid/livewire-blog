<div class="mt-12 border-t border-gray-200 pt-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">
        Comments ({{ $comments->count() + $comments->sum(fn($c) => $c->replies->count()) }})
    </h2>

    <!-- Success Message -->
    @if (session('comment-success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" wire:transition>
            <p class="text-sm text-green-800">{{ session('comment-success') }}</p>
        </div>
    @endif

    <!-- New Comment Form -->
    @auth
        <div class="mb-8 bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Leave a comment</h3>
            <form wire:submit="postComment">
                <textarea
                    wire:model="newComment"
                    rows="4"
                    placeholder="Share your thoughts..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                @error('newComment')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-4 flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Post Comment
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="mb-8 bg-gray-50 rounded-lg p-6 text-center">
            <p class="text-gray-600 mb-4">You must be logged in to comment.</p>
            <a
                href="{{ route('login') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
            >
                Login to Comment
            </a>
        </div>
    @endauth

    <!-- Comments List -->
    <div class="space-y-6">
        @forelse($comments as $comment)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <!-- Comment Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center">
                        <img
                            src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&background=4f46e5&color=fff"
                            alt="{{ $comment->user->name }}"
                            class="w-10 h-10 rounded-full mr-3"
                        >
                        <div>
                            <p class="font-medium text-gray-900">{{ $comment->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Comment Content -->
                <div class="text-gray-700 mb-4">
                    {{ $comment->content }}
                </div>

                <!-- Comment Actions -->
                <div class="flex items-center gap-4">
                    @auth
                        @if($replyingTo === $comment->id)
                            <button
                                wire:click="cancelReply"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                        @else
                            <button
                                wire:click="startReply({{ $comment->id }})"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                Reply
                            </button>
                        @endif
                    @endauth
                </div>

                <!-- Reply Form -->
                @if($replyingTo === $comment->id)
                    <div class="mt-4 bg-gray-50 rounded-lg p-4" wire:transition>
                        <form wire:submit="postReply({{ $comment->id }})">
                            <textarea
                                wire:model="replyContent"
                                rows="3"
                                placeholder="Write your reply..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            @error('replyContent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="cancelReply"
                                    class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                                >
                                    Post Reply
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Replies -->
                @if($comment->replies->count() > 0)
                    <div class="mt-6 ml-8 space-y-4 border-l-2 border-gray-200 pl-6">
                        @foreach($comment->replies as $reply)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-start mb-3">
                                    <img
                                        src="https://ui-avatars.com/api/?name={{ urlencode($reply->user->name) }}&background=6366f1&color=fff"
                                        alt="{{ $reply->user->name }}"
                                        class="w-8 h-8 rounded-full mr-3"
                                    >
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">{{ $reply->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $reply->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="text-gray-700 text-sm">
                                    {{ $reply->content }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12">
                <p class="text-gray-500">No comments yet. Be the first to share your thoughts!</p>
            </div>
        @endforelse
    </div>
</div>
