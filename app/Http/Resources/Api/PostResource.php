<?php

namespace App\Http\Resources\Api;

use App\Models\Post;
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
            'author' => AuthorResource::make($this->whenLoaded('user')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
