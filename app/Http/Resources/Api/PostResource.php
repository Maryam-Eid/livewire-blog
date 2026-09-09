<?php

namespace App\Http\Resources\Api;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * @mixin Post
 */
class PostResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canRead = ! $this->is_premium
            || ($request->user()?->hasPremiumAccess() ?? false);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when(
                $request->routeIs('api.posts.show'),
                $canRead ? $this->content : null,
            ),
            'featured_image' => $this->featuredImageUrl(),
            'status' => $this->status,
            'is_premium' => $this->is_premium,
            'can_read' => $canRead,
            'published_at' => $this->published_at,
            'views_count' => $this->views_count,
            'comments_count' => $this->whenCounted('comments'),
            'comments' => $this->when(
                $this->resource->relationLoaded('comments') && $this->comments instanceof LengthAwarePaginator,
                fn (): array => $this->paginatedComments($request),
            ),
            'author' => AuthorResource::make($this->whenLoaded('user')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array{data: mixed, links: array<string, string|null>, meta: array<string, mixed>}
     */
    private function paginatedComments(Request $request): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->comments;

        return [
            'data' => CommentResource::collection($paginator->getCollection())->resolve($request),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
