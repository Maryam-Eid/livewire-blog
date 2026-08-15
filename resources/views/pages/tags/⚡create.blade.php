<?php

use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Validate('required|string|min:2|max:255|unique:tags,name')]
    public string $name = '';

    public function save(): void
    {
        $this->validate();

        $tag = new Tag();
        $tag->name = $this->name;
        $tag->slug = Str::slug($this->name);
        $tag->save();

        session()->flash('success', 'Tag created successfully!');

        $this->redirect(route('tags.index'), navigate: true);
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Tag</h1>
        <p class="mt-1 text-sm text-gray-600">Add a tag to organize your posts.</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Name
                </label>

                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    placeholder="e.g. Laravel"
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />

                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700"
                >
                    Create Tag
                </button>

                <a
                    href="{{ route('tags.index') }}"
                    wire:navigate
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
