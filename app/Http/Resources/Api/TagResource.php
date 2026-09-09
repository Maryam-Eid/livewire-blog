<?php

namespace App\Http\Resources\Api;

use App\Models\Tag;
use Illuminate\Http\Request;

/**
 * @mixin Tag
 */
class TagResource extends Resource
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
        ];
    }
}
