<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Category\IndexCategoryRequest;
use App\Http\Requests\Api\Category\StoreCategoryRequest;
use App\Http\Requests\Api\Category\UpdateCategoryRequest;
use App\Http\Resources\Api\CategoryResource;
use App\Models\Category;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Categories', weight: 2)]
class CategoryController extends Controller
{
    #[Endpoint(
        title: 'List categories',
        description: 'Public list for filters (name, slug, color, `posts_count` of published posts). Search with `q` on name and description. Paginated 10 per page, newest first. No auth.',
    )]
    public function index(IndexCategoryRequest $request): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()
                ->withCount(['posts' => fn (Builder $posts) => $posts->published()])
                ->matchingSearch($request->validated('q'))
                ->latest()
                ->paginate(10),
        );
    }

    #[Endpoint(
        title: 'Create category',
        description: 'Same as web `/categories/create`. Requires `manage-roles`. `color` is a hex value like `#688dba`.',
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return CategoryResource::make($category->loadCount('posts'))
            ->response()
            ->setStatusCode(201);
    }

    #[Endpoint(
        title: 'Update category',
        description: 'Partial update. Same as web `/categories/{id}/edit`. Requires `manage-roles`. Changing `name` updates the slug.',
    )]
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return CategoryResource::make($category->loadCount('posts'));
    }

    #[Endpoint(
        title: 'Delete category',
        description: 'Removes the category from its posts, then deletes it. Same as web. Requires `manage-roles`.',
    )]
    public function destroy(Category $category): Response
    {
        $category->posts()->detach();
        $category->delete();

        return response()->noContent();
    }
}
