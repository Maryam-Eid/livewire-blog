<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Post $post;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:500')]
    public string $excerpt = '';

    #[Validate('required|string|min:10')]
    public string $content = '';

    #[Validate('nullable|image|max:2048')]
    public $featured_image;

    #[Validate('required|in:draft,published,archived')]
    public string $status = '';

    public string $existing_image = '';

    #[Validate('required|array|min:1')]
    public array $selectedCategories = [];

    #[Validate('nullable|array')]
    public array $selectedTags = [];

    public string $categorySearch = '';
    public string $tagSearch = '';

    public int $categoryLimit = 6;
    public int $tagLimit = 12;

    public function mount(Post $post): void
    {
        if (
            ! auth()->user()->can('edit-any-post')
            && ! (auth()->user()->can('edit-post') && $post->user_id === auth()->id())
        ) {
            abort(403);
        }

        $this->post = $post;
        $this->title = $post->title;
        $this->excerpt = $post->excerpt ?? '';
        $this->content = $post->content;
        $this->status = $post->status;
        $this->existing_image = $post->featured_image ?? '';

        $this->selectedCategories = $post->categories->pluck('id')->toArray();
        $this->selectedTags = $post->tags->pluck('id')->toArray();
    }

    public function with(): array
    {
        $categoriesQuery = Category::query()
            ->when($this->categorySearch, function ($query) {
                $query->where('name', 'like', '%' . $this->categorySearch . '%');
            })
            ->orderBy('name');

        $tagsQuery = Tag::query()
            ->when($this->tagSearch, function ($query) {
                $query->where('name', 'like', '%' . $this->tagSearch . '%');
            })
            ->orderBy('name');

        return [
            'categories' => (clone $categoriesQuery)
                ->limit($this->categoryLimit)
                ->get(),

            'categoryTotal' => (clone $categoriesQuery)->count(),

            'tags' => (clone $tagsQuery)
                ->limit($this->tagLimit)
                ->get(),

            'tagTotal' => (clone $tagsQuery)->count(),
        ];
    }

    public function updatingCategorySearch(): void
    {
        $this->categoryLimit = 6;
    }

    public function updatingTagSearch(): void
    {
        $this->tagLimit = 12;
    }

    public function loadMoreCategories(): void
    {
        $this->categoryLimit += 6;
    }

    public function loadMoreTags(): void
    {
        $this->tagLimit += 12;
    }

    public function update(): void
    {
        $this->validate();

        $this->post->title = $this->title;
        $this->post->slug = Str::slug($this->title);
        $this->post->excerpt = $this->excerpt;
        $this->post->content = $this->content;
        $this->post->status = $this->status;

        if ($this->featured_image) {
            if ($this->existing_image && ! Str::startsWith($this->existing_image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($this->existing_image);
            }

            $path = $this->featured_image->store('posts', 'public');

            $this->post->featured_image = $path;
            $this->existing_image = $path;
        }

        if ($this->status === 'published' && ! $this->post->published_at) {
            $this->post->published_at = now();
        }

        $this->post->save();

        $this->post->categories()->sync($this->selectedCategories);
        $this->post->tags()->sync($this->selectedTags);

        session()->flash('success', 'Post updated successfully!');

        $this->redirect(route('posts.index'), navigate: true);
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Post</h1>
        <p class="mt-1 text-sm text-gray-600">Update your blog post</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="update" class="space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="required-label block text-sm font-medium text-gray-700">
                    Title
                </label>

                <input
                    id="title"
                    type="text"
                    wire:model.live.debounce="title"
                    placeholder="Enter post title"
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Excerpt -->
            <div>
                <label for="excerpt" class="block text-sm font-medium text-gray-700">
                    Excerpt
                </label>

                <textarea
                    id="excerpt"
                    wire:model="excerpt"
                    rows="2"
                    placeholder="A short summary of your post"
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>

                @error('excerpt')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="required-label block text-sm font-medium text-gray-700">
                    Content
                </label>

                <div wire:ignore>
                    <input id="x-content" type="hidden" name="content" value="{{ $content }}">

                    <trix-editor
                        input="x-content"
                        class="trix-content"
                        x-data
                        x-on:trix-change="$wire.content = $event.target.value"
                    ></trix-editor>
                </div>

                @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Featured Image -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Featured Image
                </label>

                @if ($existing_image && ! $featured_image)
                    <div class="mb-3 mt-2">
                        <p class="mb-1 text-sm text-gray-600">Current image:</p>

                        <img
                            src="{{ $post->featuredImageUrl() }}"
                            alt="Current image"
                            class="h-32 w-auto rounded border border-gray-300"
                        >
                    </div>
                @endif

                <input
                    type="file"
                    wire:model="featured_image"
                    accept="image/*"
                    class="mt-1 block w-full p-2 text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                >

                @error('featured_image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($featured_image)
                    <div class="mt-3" wire:transition>
                        <p class="mb-1 text-sm text-gray-600">New image:</p>

                        <img
                            src="{{ $featured_image->temporaryUrl() }}"
                            alt="Preview"
                            class="h-32 w-auto rounded border border-gray-300"
                        >
                    </div>
                @endif

                <div wire:loading wire:target="featured_image" class="mt-2 text-sm text-gray-500">
                    Uploading...
                </div>
            </div>

            <!-- Categories -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <label class="required-label block text-sm font-medium text-gray-700">
                        Categories
                    </label>

                    <span class="text-xs text-gray-500">
                        {{ count($selectedCategories) }} selected
                    </span>
                </div>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="categorySearch"
                    placeholder="Search categories..."
                    class="mb-3 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($categories as $category)
                        <label wire:key="category-{{ $category->id }}" class="cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="selectedCategories"
                                value="{{ $category->id }}"
                                class="peer sr-only"
                            >

                            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 transition hover:border-indigo-300 hover:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="h-4 w-4 shrink-0 rounded-full"
                                        style="background-color: {{ $category->color }}"
                                    ></span>

                                    <span class="truncate text-sm font-medium text-gray-700">
                                        {{ $category->name }}
                                    </span>
                                </div>

                                <svg
                                    class="h-5 w-5 shrink-0 text-indigo-600 opacity-0 peer-checked:opacity-100"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </label>
                    @empty
                        <p class="col-span-full py-4 text-center text-sm text-gray-500">
                            No categories found.
                        </p>
                    @endforelse
                </div>

                @if ($categoryTotal > $categories->count())
                    <div class="mt-4 text-center">
                        <button
                            type="button"
                            wire:click="loadMoreCategories"
                            class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                        >
                            Show more categories
                        </button>
                    </div>
                @endif

                @error('selectedCategories')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tags -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">
                        Tags
                    </label>

                    <span class="text-xs text-gray-500">
                        {{ count($selectedTags) }} selected
                    </span>
                </div>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="tagSearch"
                    placeholder="Search tags..."
                    class="mb-3 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                <div class="flex flex-wrap gap-2">
                    @forelse ($tags as $tag)
                        <label wire:key="tag-{{ $tag->id }}" class="cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="selectedTags"
                                value="{{ $tag->id }}"
                                class="peer sr-only"
                            >

                            <span class="inline-flex rounded-full border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 transition hover:border-indigo-300 hover:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white">
                                #{{ $tag->name }}
                            </span>
                        </label>
                    @empty
                        <p class="w-full py-4 text-center text-sm text-gray-500">
                            No tags found.
                        </p>
                    @endforelse
                </div>

                @if ($tagTotal > $tags->count())
                    <div class="mt-4 text-center">
                        <button
                            type="button"
                            wire:click="loadMoreTags"
                            class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                        >
                            Show more tags
                        </button>
                    </div>
                @endif

                @error('selectedTags')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="required-label mb-2 block text-sm font-medium text-gray-700">
                    Status
                </label>

                <div class="space-y-2">
                    <label class="flex items-start">
                        <input
                            type="radio"
                            wire:model="status"
                            value="draft"
                            class="mt-1 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >

                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-700">Draft</span>
                            <span class="block text-sm text-gray-500">
                                Save as draft, not visible to readers.
                            </span>
                        </div>
                    </label>

                    @can('publish-post')
                        <label class="flex items-start">
                            <input
                                type="radio"
                                wire:model="status"
                                value="published"
                                class="mt-1 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">Published</span>
                                <span class="block text-sm text-gray-500">
                                    Publish immediately, visible to all readers.
                                </span>
                            </div>
                        </label>

                        <label class="flex items-start">
                            <input
                                type="radio"
                                wire:model="status"
                                value="archived"
                                class="mt-1 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">Archived</span>
                                <span class="block text-sm text-gray-500">
                                    Hide this post from readers while keeping it saved.
                                </span>
                            </div>
                        </label>
                    @endcan
                </div>

                @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="cursor-pointer inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Update Post
                </button>

                <a
                    href="{{ route('posts.index') }}"
                    wire:navigate
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
