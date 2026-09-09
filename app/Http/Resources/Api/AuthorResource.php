<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * @mixin User
 */
class AuthorResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials(),
        ];
    }
}
