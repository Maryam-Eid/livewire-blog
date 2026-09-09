<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Post\IndexPostRequest;
use App\Http\Requests\Api\Post\ManagePostRequest;
use App\Http\Requests\Api\Post\StorePostRequest;
use App\Http\Requests\Api\Post\UpdatePostRequest;
use App\Http\Resources\Api\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Support\PostPublication;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Posts', weight: 1)]
class PostController extends Controller
{
    #[Endpoint(
        title: 'Published posts',
        description: 'Public published feed. Filter with `q`, `category` (slug), `tag` (slug), `tags` (slugs), and `access` (`all`, `free`, `premium`). Matches the web blog listing. Send a Bearer token so `can_read` reflects premium/staff access.',
    )]
    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        return PostResource::collection(
            $this->posts()
                ->published()
                ->matchingSearch($request->validated('q'))
                ->forCategory($request->validated('category'))
                ->forTags($request->tagSlugs())
                ->forAccess($request->validated('access'))
                ->latest('published_at')
                ->paginate(9),
        );
    }

    #[Endpoint(
        title: 'Manage posts',
        description: 'Staff listing, same as web `/posts`. Requires `create-post`. Authors only see their own posts (any status). Editors and admins see every author and status. Filter with `q`, `status`, and `access`.',
    )]
    public function manage(ManagePostRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $status = $request->validated('status');

        return PostResource::collection(
            $this->posts()
                ->when($user->hasRole('author'), fn (Builder $query) => $query->where('user_id', $user->id))
                ->matchingSearch($request->validated('q'))
                ->when(filled($status) && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
                ->forAccess($request->validated('access'))
                ->latest()
                ->paginate(10),
        );
    }

    #[Endpoint(
        title: 'Post detail',
        description: 'Published post by id. Drafts and scheduled posts return 404 unless the user can edit that post. Send a Bearer token to unlock premium `content` (active Premium subscription or `create-post`).',
    )]
    public function show(Request $request, Post $post): PostResource
    {
        abort_unless($post->isPubliclyVisible() || $post->canBeEditedBy($request->user()), 404);

        if ($post->isPubliclyVisible() && ! $post->canBeEditedBy($request->user())) {
            $post->increment('views_count');
            $post->views()->create([
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'viewed_at' => now(),
            ]);
        }

        return $this->resource($post);
    }

    #[Endpoint(
        title: 'Create post',
        description: 'Requires `create-post`. Publishing or scheduling also requires `publish-post`. At least one category is required. `featured_image` can be an image upload (multipart) or a URL, same 2MB limit as the web form.',
    )]
    public function store(StorePostRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validated();

        $post = $user->posts()->make([
            ...collect($validated)->except(['featured_image', 'category_ids', 'tag_ids', 'scheduled_at'])->all(),
            'is_premium' => $request->boolean('is_premium'),
            'published_at' => PostPublication::publishedAt($validated['status'], $validated['scheduled_at'] ?? null),
        ]);
        $post->applyFeaturedImage($request->file('featured_image') ?? $request->input('featured_image'));
        $post->save();

        $post->categories()->attach($validated['category_ids']);
        $post->tags()->attach($validated['tag_ids'] ?? []);

        return $this->resource($post)->response()->setStatusCode(201);
    }

    #[Endpoint(
        title: 'Update post',
        description: 'Partial update. Requires `create-post` plus `edit-post` on own posts or `edit-any-post`. Setting status to scheduled, published, or archived requires `publish-post`. Send `status: draft` to unpublish.',
    )]
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? $post->status;

        $post->fill(collect($validated)->except(['featured_image', 'category_ids', 'tag_ids', 'scheduled_at'])->all() + [
            'status' => $status,
            'published_at' => PostPublication::publishedAt(
                $status,
                $validated['scheduled_at'] ?? $post->published_at?->toIso8601String(),
                $post,
            ),
            ...array_key_exists('is_premium', $validated) ? ['is_premium' => $request->boolean('is_premium')] : [],
        ]);

        if ($request->hasFile('featured_image') || $request->exists('featured_image')) {
            $post->applyFeaturedImage($request->file('featured_image') ?? $request->input('featured_image'));
        }

        $post->save();

        if (array_key_exists('category_ids', $validated)) {
            $post->categories()->sync($validated['category_ids']);
        }

        if (array_key_exists('tag_ids', $validated)) {
            $post->tags()->sync($validated['tag_ids'] ?? []);
        }

        return $this->resource($post);
    }

    private function posts(): Builder
    {
        return Post::query()->with(['user', 'categories', 'tags'])->withCount('comments');
    }

    private function resource(Post $post): PostResource
    {
        return PostResource::make(
            $post->loadMissing(['user', 'categories', 'tags'])->loadCount('comments'),
        );
    }
}
