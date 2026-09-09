<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    }

    protected static function newCollection($resource): ResourceCollection
    {
        return new ResourceCollection($resource, static::class);
    }
}
