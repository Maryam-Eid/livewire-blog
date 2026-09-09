<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tag\IndexTagRequest;
use App\Http\Requests\Api\Tag\StoreTagRequest;
use App\Http\Requests\Api\Tag\UpdateTagRequest;
use App\Http\Resources\Api\TagResource;
use App\Models\Tag;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Tags', weight: 3)]
class TagController extends Controller
{
    #[Endpoint(
        title: 'List tags',
        description: 'Public list for filters (name, slug, `posts_count` of published posts). Search with `q` on name. Paginated 10 per page, newest first. No auth.',
    )]
    public function index(IndexTagRequest $request): AnonymousResourceCollection
    {
        return TagResource::collection(
            Tag::query()
                ->withCount(['posts' => fn (Builder $posts) => $posts->published()])
                ->matchingSearch($request->validated('q'))
                ->latest()
                ->paginate(10),
        );
    }

    #[Endpoint(
        title: 'Create tag',
        description: 'Same as web `/tags/create`. Requires `manage-roles`. `name` is required, min 2 characters, and unique.',
    )]
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::query()->create($request->validated());

        return TagResource::make($tag->loadCount('posts'))
            ->response()
            ->setStatusCode(201);
    }

    #[Endpoint(
        title: 'Update tag',
        description: 'Partial update. Same as web `/tags/{id}/edit`. Requires `manage-roles`. Changing `name` updates the slug.',
    )]
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $tag->update($request->validated());

        return TagResource::make($tag->loadCount('posts'));
    }

    #[Endpoint(
        title: 'Delete tag',
        description: 'Removes the tag from its posts, then deletes it. Same as web. Requires `manage-roles`.',
    )]
    public function destroy(Tag $tag): Response
    {
        $tag->posts()->detach();
        $tag->delete();

        return response()->noContent();
    }
}
