<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Post\StoreCommentRequest;
use App\Http\Resources\Api\CommentResource;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

#[Group('Comments', weight: 4)]
class CommentController extends Controller
{
    #[Endpoint(
        title: 'Post comments',
        description: 'Paginated approved top-level comments (10 per page) with nested replies. Same visibility as the website post page: published posts only, and premium posts require Premium access or `create-post`. Use `page`.',
    )]
    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        $this->ensureCommentable($request, $post);

        return CommentResource::collection(
            $post->comments()
                ->approved()
                ->topLevel()
                ->with(['user', 'replies.user'])
                ->latest()
                ->paginate(10),
        );
    }

    #[Endpoint(
        title: 'Add comment',
        description: 'Same as the website comment form. Requires a Bearer token. `content` is min 3 / max 1000. Optional `parent_id` replies to a top-level comment on this post. Status is approved. The post author is notified unless they commented on their own post.',
    )]
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $this->ensureCommentable($request, $post);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $parentId = $request->validated('parent_id');

        if ($parentId !== null) {
            $parent = $post->comments()
                ->approved()
                ->topLevel()
                ->whereKey($parentId)
                ->first();

            if ($parent === null) {
                throw ValidationException::withMessages([
                    'parent_id' => 'The selected parent comment is invalid.',
                ]);
            }
        }

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $request->validated('content'),
            'status' => 'approved',
        ]);

        if ($post->user_id !== $user->id) {
            $post->user->notify(new NewCommentNotification($comment));
        }

        return CommentResource::make($comment->load(['user', 'replies.user']))
            ->response()
            ->setStatusCode(201);
    }

    private function ensureCommentable(Request $request, Post $post): void
    {
        abort_unless($post->isPubliclyVisible(), 404);
        abort_unless($post->canBeReadBy($request->user()), 403);
    }
}
