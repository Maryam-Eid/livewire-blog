<?php

use App\Models\Category;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->withCount('posts')
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('description', 'like', '%' . $this->search . '%');
                    });
                })
                ->latest()
                ->paginate(10),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCategory(Category $category): void
    {
        $category->posts()->detach();
        $category->delete();

        session()->flash('success', 'Category deleted successfully!');
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
        <p class="mt-1 text-sm text-gray-600">Manage post categories</p>
    </div>

    <!-- Filters and Actions -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories..."
                    class="block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <a
                href="{{ route('categories.create') }}"
                wire:navigate
                class="inline-flex cursor-pointer items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>

                New Category
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4" wire:transition>
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Categories Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Category
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Posts
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Created
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                        Actions
                    </th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}" class="transition hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                    <span
                                        class="h-4 w-4 shrink-0 rounded-full"
                                        style="background-color: {{ $category->color }}"
                                    ></span>

                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $category->name }}
                                    </p>

                                    @if ($category->description)
                                        <p class="mt-1 max-w-md truncate text-sm text-gray-500">
                                            {{ $category->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                            {{ $category->posts_count }}
                            {{ Str::plural('post', $category->posts_count) }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $category->created_at->format('M d, Y') }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Edit category" position="top">
                                    <a
                                        href="{{ route('categories.edit', $category) }}"
                                        wire:navigate
                                        class="inline-flex size-8 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800"
                                    >
                                        <flux:icon.pencil-square class="size-5" />
                                        <span class="sr-only">Edit</span>
                                    </a>
                                </flux:tooltip>

                                <flux:tooltip content="Delete category" position="top">
                                    <button
                                        type="button"
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="Delete this category? It will be removed from its posts."
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                    >
                                        <flux:icon.trash class="size-5" />
                                        <span class="sr-only">Delete</span>
                                    </button>
                                </flux:tooltip>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">
                            No categories found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
</div>
