<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public Category $category;

    public string $name = '';
    public string $description = '';
    public string $color = '#688dba';

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->color = $category->color;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255', Rule::unique('categories', 'name')->ignore($this->category->id)],
            'description' => 'nullable|string|max:1000',
            'color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/'
        ];
    }

    public function update(): void
    {
        $this->validate();

        $this->category->name = $this->name;
        $this->category->slug = Str::slug($this->name);
        $this->category->description = $this->description;
        $this->category->color = $this->color;
        $this->category->save();

        session()->flash('success', 'Category updated successfully!');

        $this->redirect(route('categories.index'), navigate: true);
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Category</h1>
        <p class="mt-1 text-sm text-gray-600">Update category details.</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="update" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Name
                </label>

                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />

                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">
                    Description <span class="text-gray-400">(Optional)</span>
                </label>

                <textarea
                    id="description"
                    wire:model="description"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>

                @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="color" class="block text-sm font-medium text-gray-700">
                    Color
                </label>

                <div class="mt-1 flex items-center gap-3">
                    <input
                        id="color"
                        type="color"
                        wire:model="color"
                        class="h-12 w-12 cursor-pointer"
                    />

                    <span class="text-sm text-gray-500">{{ $color }}</span>
                </div>

                @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700"
                >
                    Update Category
                </button>

                <a
                    href="{{ route('categories.index') }}"
                    wire:navigate
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
