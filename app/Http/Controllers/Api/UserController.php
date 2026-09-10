<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\IndexUserRequest;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Users', weight: 5)]
class UserController extends Controller
{
    #[Endpoint(
        title: 'List users',
        description: 'Same as web `/users`. Requires `manage-users`. Filter with `q` (name or email) and `role` (`all` or a role name). Paginated 10 per page, newest first.',
    )]
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $role = $request->validated('role');

        return UserResource::collection(
            User::query()
                ->with('roles')
                ->when($request->validated('q'), function (Builder $query, string $search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                })
                ->when(filled($role) && $role !== 'all', fn (Builder $query) => $query->whereHas(
                    'roles',
                    fn (Builder $roles) => $roles->where('name', $role),
                ))
                ->latest()
                ->paginate(10),
        );
    }

    #[Endpoint(
        title: 'Create user',
        description: 'Same as web `/users/create`. Requires `manage-users`. `roles` is an array of role names (at least one). Password min 8 characters and must match `password_confirmation`.',
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'password']));
        $user->assignRole($request->validated('roles'));

        return UserResource::make($user->load('roles'))->response()->setStatusCode(201);
    }

    #[Endpoint(
        title: 'User detail',
        description: 'Requires `manage-users`.',
    )]
    public function show(User $user): UserResource
    {
        return UserResource::make($user->load('roles'));
    }

    #[Endpoint(
        title: 'Update user',
        description: 'Same as web `/users/{id}/edit`. Requires `manage-users`. Password is optional (min 8) and must match `password_confirmation` when sent. `roles` replaces the assigned roles when sent.',
    )]
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $validated = $request->validated();

        $user->fill(collect($validated)->only(['name', 'email'])->all());

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles']);
        }

        return UserResource::make($user->load('roles'));
    }

    #[Endpoint(
        title: 'Delete user',
        description: 'Same as web. Requires `manage-users`. You cannot delete your own account.',
    )]
    public function destroy(User $user): Response
    {
        abort_if($user->is(request()->user()), 403, 'You cannot delete your own account!');

        $user->delete();

        return response()->noContent();
    }
}
