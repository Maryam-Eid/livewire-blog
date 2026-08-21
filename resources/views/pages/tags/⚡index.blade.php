<?php

use App\Models\Tag;
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
            'tags' => Tag::query()
                ->withCount('posts')
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteTag(Tag $tag): void
    {
        $tag->posts()->detach();
        $tag->delete();

        session()->flash('success', 'Tag deleted successfully!');
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Tags</h1>
        <p class="mt-1 text-sm text-gray-600">Manage post tags</p>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search tags..."
                class="p-2 flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />

            <a
                href="{{ route('tags.create') }}"
                wire:navigate
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                 New Tag
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Tag
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
                @forelse ($tags as $tag)
                    <tr wire:key="tag-{{ $tag->id }}" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">
                                    #{{ $tag->name }}
                                </span>
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $tag->posts_count }}
                            {{ Str::plural('post', $tag->posts_count) }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $tag->created_at->format('M d, Y') }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex justify-end gap-3">
                                <a
                                    href="{{ route('tags.edit', $tag) }}"
                                    wire:navigate
                                    class="cursor-pointer rounded-md px-2 py-1 text-indigo-600 transition hover:bg-indigo-100 hover:text-indigo-900"
                                >
                                    Edit
                                </a>

                                <button
                                    wire:click="deleteTag({{ $tag->id }})"
                                    wire:confirm="Delete this tag? It will be removed from its posts."
                                    class="cursor-pointer rounded-md px-2 py-1 text-red-600 transition hover:bg-red-100 hover:text-red-900"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">
                            No tags found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $tags->links() }}
    </div>
</div>
