<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Role\IndexRoleRequest;
use App\Http\Resources\Api\RoleResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

#[Group('Users', weight: 5)]
class RoleController extends Controller
{
    #[Endpoint(
        title: 'List roles',
        description: 'Role names for the user list filter and create/edit role checkboxes. Same as web `/users`. Requires `manage-users`.',
    )]
    public function index(IndexRoleRequest $request): AnonymousResourceCollection
    {
        return RoleResource::collection(
            Role::query()->orderBy('name')->get(),
        );
    }
}
