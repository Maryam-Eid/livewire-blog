<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * @mixin Role
 */
class RoleResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
