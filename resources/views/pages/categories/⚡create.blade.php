<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Validate('required|string|min:2|max:255|unique:categories,name')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public string $color = '#688dba';

    public function save(): void
    {
        $this->validate();

        $category = new Category();
        $category->name = $this->name;
        $category->slug = Str::slug($this->name);
        $category->description = $this->description;
        $category->color = $this->color;
        $category->save();

        session()->flash('success', 'Category created successfully!');

        $this->redirect(route('categories.index'), navigate: true);
    }
};
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Category</h1>
        <p class="mt-1 text-sm text-gray-600">Add a new category for your posts</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="required-label block text-sm font-medium text-gray-700">
                    Name
                </label>

                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    placeholder="e.g. Technology"
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">
                    Description
                </label>

                <textarea
                    id="description"
                    wire:model="description"
                    rows="4"
                    placeholder="Describe this category..."
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>

                @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Color -->
            <div>
                <label for="color" class="required-label block text-sm font-medium text-gray-700">
                    Color
                </label>

                <div class="mt-2 flex items-center gap-3">
                    <input
                        id="color"
                        type="color"
                        wire:model.live="color"
                        class="h-12 w-12 cursor-pointer appearance-none overflow-hidden rounded-full border-0 p-0 [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:rounded-full [&::-webkit-color-swatch]:border-0"
                    >

                    <span class="text-sm text-gray-500">
                        {{ $color }}
                    </span>
                </div>

                @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="cursor-pointer inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Create Category
                </button>

                <a
                    href="{{ route('categories.index') }}"
                    wire:navigate
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
