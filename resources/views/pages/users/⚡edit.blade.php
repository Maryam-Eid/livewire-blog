<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component {
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $password = '';

    public array $selectedRoles = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'selectedRoles' => ['required', 'array', 'min:1'],
        ];
    }

    public function with(): array
    {
        return [
            'roles' => Role::query()
                ->orderBy('name')
                ->get(),
        ];
    }

    public function update(): void
    {
        $this->validate();

        $this->user->name = $this->name;
        $this->user->email = $this->email;

        if ($this->password) {
            $this->user->password = Hash::make($this->password);
        }

        $this->user->save();

        $this->user->syncRoles($this->selectedRoles);

        session()->flash('success', 'User updated successfully!');

        $this->redirect(route('users.index'), navigate: true);
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
        <p class="mt-1 text-sm text-gray-600">Update user information</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="update" class="space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="required-label block text-sm font-medium text-gray-700">
                    Name
                </label>

                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    placeholder="Enter user's full name"
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="required-label block text-sm font-medium text-gray-700">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    placeholder="user@example.com"
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    New Password
                </label>

                <div x-data="{ showPassword: false }" class="relative mt-1">
                    <input
                        id="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        wire:model="password"
                        placeholder="Minimum 8 characters"
                        class="block w-full rounded-md border-gray-300 p-2 pr-12 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                        x-bind:title="showPassword ? 'Hide password' : 'Show password'"
                        class="cursor-pointer absolute inset-y-0 right-0 my-auto mr-2 flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <svg
                            x-show="!showPassword"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.4 4-4.7 6-9 6s-7.6-2-9-6c1.4-4 4.7-6 9-6s7.6 2 9 6z"
                            />
                        </svg>

                        <svg
                            x-show="showPassword"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.3 0-7.6-2-9-6a10.4 10.4 0 012.6-3.8M9.88 9.88A3 3 0 0012 15a3 3 0 002.12-5.12M3 3l18 18M14.12 14.12L9.88 9.88M6.7 6.7A9.9 9.9 0 0112 5c4.3 0 7.6 2 9 6a10.2 10.2 0 01-1.5 2.7"
                            />
                        </svg>
                    </button>
                </div>

                @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-1 text-sm text-gray-500">
                    Leave blank to keep the current password.
                </p>
            </div>

            <!-- Roles -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <label class="required-label block text-sm font-medium text-gray-700">
                        Roles
                    </label>

                    <span class="text-xs text-gray-500">
                        {{ count($selectedRoles) }} selected
                    </span>
                </div>

                <div class="flex flex-wrap gap-3">
                    @foreach($roles as $role)
                        <label wire:key="role-{{ $role->id }}" class="cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="selectedRoles"
                                value="{{ $role->name }}"
                                class="peer sr-only"
                            >

                            <span class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
                                {{ ucfirst($role->name) }}
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('selectedRoles')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Update User
                </button>

                <a
                    href="{{ route('users.index') }}"
                    wire:navigate
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
