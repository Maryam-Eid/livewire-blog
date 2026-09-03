<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = 'all';

    public function with(): array
    {
        $query = User::with('roles')->latest();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%');
        }

        if ($this->roleFilter !== 'all') {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->roleFilter);
            });
        }

        return [
            'users' => $query->paginate(10),
            'roles' => Role::all(),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function deleteUser(User $user): void
    {
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account!');
            return;
        }

        $user->delete();
        session()->flash('success', 'User deleted successfully!');
    }
};
?>


<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Users</h1>
        <p class="mt-1 text-sm text-gray-600">Manage user accounts and roles</p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4 items-center">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search users..."
                    class="p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="sm:w-48">
                <select
                    wire:model.live="roleFilter"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="all">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <a href="{{ route('users.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New User
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" wire:transition>
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4" wire:transition>
            <p class="text-sm text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Users Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Roles
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Joined
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr wire:key="user-{{ $user->id }}" wire:transition class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full"
                                         src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=4f46e5&color=fff"
                                         alt="">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                @empty
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            No role
                                        </span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Edit user" position="top">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="inline-flex size-8 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800">
                                        <flux:icon.pencil-square class="size-5" />
                                        <span class="sr-only">Edit</span>
                                    </a>
                                </flux:tooltip>

                                @if($user->id !== auth()->id())
                                    <flux:tooltip content="Delete user" position="top">
                                        <button
                                            type="button"
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user?"
                                            class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                        >
                                            <flux:icon.trash class="size-5" />
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </flux:tooltip>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No users found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $users->links() }}
    </div>

</div>
