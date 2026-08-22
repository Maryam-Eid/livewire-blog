<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:500')]
    public string $excerpt = '';

    #[Validate('required|string|min:10')]
    public string $content = '';

    #[Validate('nullable|image|max:2048')]
    public $featured_image;

    #[Validate('required|in:draft,scheduled,published')]
    public string $status = 'draft';

    #[Validate('required_if:status,scheduled|nullable|date_format:Y-m-d H:i')]
    public ?string $scheduledAt = null;

    #[Validate('required|array|min:1')]
    public array $selectedCategories = [];

    #[Validate('nullable|array')]
    public array $selectedTags = [];

    public string $categorySearch = '';
    public string $tagSearch = '';

    public int $categoryLimit = 6;
    public int $tagLimit = 12;

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

            'categoryTotal' => $categoriesQuery->count(),

            'tags' => (clone $tagsQuery)
                ->limit($this->tagLimit)
                ->get(),

            'tagTotal' => $tagsQuery->count(),
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

    public function save(): void
    {
        $this->validate();

        if (in_array($this->status, ['scheduled', 'published'], true) && ! auth()->user()->can('publish-post')) {
            abort(403);
        }

        $scheduledAt = $this->scheduledPublicationAt();

        $post = new Post();
        $post->user_id = auth()->id();
        $post->title = $this->title;
        $post->slug = Str::slug($this->title);
        $post->excerpt = $this->excerpt;
        $post->content = $this->content;
        $post->status = $this->status;

        if ($this->featured_image) {
            $post->featured_image = $this->featured_image->store('posts', 'public');
        }

        $post->published_at = match ($this->status) {
            'published' => now(),
            'scheduled' => $scheduledAt,
            default => null,
        };

        $post->save();

        $post->categories()->attach($this->selectedCategories);

        if ($this->selectedTags !== []) {
            $post->tags()->attach($this->selectedTags);
        }

        session()->flash('success', 'Post created successfully!');

        $this->redirect(route('posts.index'), navigate: true);
    }

    private function scheduledPublicationAt(): ?CarbonImmutable
    {
        if ($this->status !== 'scheduled') {
            return null;
        }

        $scheduledAt = CarbonImmutable::parse(
            $this->scheduledAt,
            config('app.timezone'),
        );

        if ($scheduledAt->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'scheduledAt' => 'Choose a future date and time.',
            ]);
        }

        return $scheduledAt;
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create New Post</h1>
        <p class="mt-1 text-sm text-gray-600">Write and publish your blog post</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form wire:submit="save" class="space-y-6">
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
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />

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

                <p class="mt-1 text-sm text-gray-500">
                    This will appear in post previews and search results.
                </p>
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="required-label block text-sm font-medium text-gray-700">
                    Content
                </label>

                <div wire:ignore>
                    <input id="x-content" type="hidden" name="content">

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

                <input
                    type="file"
                    wire:model="featured_image"
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                />

                @error('featured_image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($featured_image)
                    <div class="mt-3" wire:transition>
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

                            <div
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-3 transition hover:border-indigo-300 hover:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"/>
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

                            <span
                                class="inline-flex rounded-full border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 transition hover:border-indigo-300 hover:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white">
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
                            wire:model.live="status"
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
                                wire:model.live="status"
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
                                wire:model.live="status"
                                value="scheduled"
                                class="mt-1 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">Scheduled</span>
                                <span class="block text-sm text-gray-500">
                                    Publish automatically at a future date and time.
                                </span>
                            </div>
                        </label>

                        @if ($status === 'scheduled')
                            <div class="ml-7 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800" wire:transition>
                                <label for="scheduledAt" class="required-label block text-sm font-medium text-gray-700">
                                    Publication date and time
                                </label>
                                <div
                                    wire:ignore
                                    x-data="{
                                        picker: null,
                                        init() {
                                            this.picker = window.flatpickr(this.$refs.input, {
                                                enableTime: true,
                                                dateFormat: 'Y-m-d H:i',
                                                altInput: true,
                                                altFormat: 'F j, Y at h:i K',
                                                defaultDate: @js($scheduledAt),
                                                minDate: new Date(),
                                                minuteIncrement: 15,
                                                onChange: (dates, value) => $wire.set('scheduledAt', value),
                                            });
                                        },
                                        destroy() {
                                            this.picker?.destroy();
                                        },
                                    }"
                                >
                                    <input
                                        id="scheduledAt"
                                        x-ref="input"
                                        type="text"
                                        placeholder="Choose publication date and time"
                                        class="mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <p class="mt-1 text-xs text-gray-500">
                                    Timezone: {{ config('app.timezone') }}
                                </p>
                                @error('scheduledAt')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
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
                    Create Post
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
