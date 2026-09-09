<?php

namespace App\Http\Resources\Api;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * @mixin Category
 */
class CategoryResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when(
                $request->routeIs('api.categories.*'),
                $this->description,
            ),
            'color' => $this->color,
            'posts_count' => $this->whenCounted('posts'),
            'created_at' => $this->when(
                $request->routeIs('api.categories.*'),
                $this->created_at,
            ),
            'updated_at' => $this->when(
                $request->routeIs('api.categories.*'),
                $this->updated_at,
            ),
        ];
    }
}
