<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResourceCollection extends AnonymousResourceCollection
{
    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    }
}
