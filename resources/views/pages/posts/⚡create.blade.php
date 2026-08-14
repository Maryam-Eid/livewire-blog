<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
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

    #[Validate('required|in:draft,published')]
    public string $status = 'draft';

    #[Validate('required|array|min:1')]
    public array $selectedCategories = [];

    #[Validate('nullable|array')]
    public array $selectedTags = [];

    public function with(): array
    {
        return [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ];
    }

    public function save()
    {
        $this->validate();

        $post = new Post();
        $post->user_id = auth()->id();
        $post->title = $this->title;
        $post->slug = Str::slug($this->title);
        $post->excerpt = $this->excerpt;
        $post->content = $this->content;
        $post->status = $this->status;

        if ($this->featured_image) {
            $path = $this->featured_image->store('posts', 'public');
            $post->featured_image = $path;
        }

        if ($this->status === 'published') {
            $post->published_at = now();
        }

        $post->save();

        $post->categories()->attach($this->selectedCategories);

        if (!empty($this->selectedTags)) {
            $post->tags()->attach($this->selectedTags);
        }

        session()->flash('success', 'Post created successfully!');

        $this->redirect(route('posts.index'), navigate: true);
    }
};
?>


<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create New Post</h1>
        <p class="mt-1 text-sm text-gray-600">Write and publish your blog post</p>
    </div>

    {{-- form --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form wire:submit="save" class="space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">
                    Title
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model.live.debounce="title"
                    placeholder="Enter post title"
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                    placeholder="A short summary of your post (optional)"
                    rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                @error('excerpt')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">This will appear in post previews and search results</p>
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700">
                    Content
                </label>
                <div wire:ignore>
                    <input type="hidden" name="content" id="x-content">
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
                    class="mt-1 block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100"
                />
                @error('featured_image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($featured_image)
                    <div class="mt-3" wire:transition>
                        <img src="{{ $featured_image->temporaryUrl() }}"
                             class="h-32 w-auto rounded border border-gray-300" alt="Preview">
                    </div>
                @endif

                <div wire:loading wire:target="featured_image" class="mt-2 text-sm text-gray-500">
                    Uploading...
                </div>
            </div>

            <!-- Categories -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Categories (Required)
                </label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                    @foreach($categories as $category)
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model="selectedCategories"
                                value="{{ $category->id }}"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-3 flex items-center">
                                <span
                                    class="inline-block w-3 h-3 rounded-full mr-2"
                                    style="background-color: {{ $category->color }}"
                                ></span>
                                <span class="text-sm font-medium text-gray-700">{{ $category->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('selectedCategories')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tags (Optional)
                </label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                    @foreach($tags as $tag)
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model="selectedTags"
                                value="{{ $tag->id }}"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedTags')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Select relevant tags to help readers find your content</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status
                </label>
                <div class="space-y-2">
                    <label class="flex items-start">
                        <input
                            type="radio"
                            wire:model="status"
                            value="draft"
                            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                        />
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-700">Draft</span>
                            <span class="block text-sm text-gray-500">Save as draft, not visible to readers</span>
                        </div>
                    </label>

                    @can('publish-post')
                        <label class="flex items-start">
                            <input
                                type="radio"
                                wire:model="status"
                                value="published"
                                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                            />
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">Published</span>
                                <span
                                    class="block text-sm text-gray-500">Publish immediately, visible to all readers</span>
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
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Create Post
                </button>
                <a
                    href="{{ route('posts.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
