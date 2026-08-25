<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PostList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public string $selectedCategory = '';

    #[Url(as: 'tag')]
    public string $selectedTag = '';

    #[Url(as: 'access')]
    public string $accessFilter = 'all';

    #[Layout('layouts.public')]
    #[Title('Blog')]
    public function render()
    {
        $posts = Post::with(['user', 'categories', 'tags'])
            ->published()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%')
                        ->orWhere('excerpt', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('categories', function ($q) {
                    $q->where('slug', $this->selectedCategory);
                });
            })
            ->when($this->selectedTag, function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->where('slug', $this->selectedTag);
                });
            })
            ->when($this->accessFilter === 'free', fn ($query) => $query->where('is_premium', false))
            ->when($this->accessFilter === 'premium', fn ($query) => $query->where('is_premium', true))
            ->latest('published_at')
            ->paginate(9);

        return view('livewire.post-list',
            [
                'posts' => $posts,
                'categories' => Category::withCount('posts')->get(),
                'tags' => Tag::withCount('posts')->get(),
                'hasPremiumAccess' => auth()->user()?->hasPremiumAccess() ?? false,
            ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedTag(): void
    {
        $this->resetPage();
    }

    public function updatingAccessFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->selectedTag = '';
        $this->accessFilter = 'all';
        $this->resetPage();
    }

    public function selectCategory(string $slug = ''): void
    {
        $this->selectedCategory = $slug;
        $this->resetPage();
    }

    public function selectTag(string $slug = ''): void
    {
        $this->selectedTag = $slug;
        $this->resetPage();
    }

    public function selectAccess(string $access = 'all'): void
    {
        abort_unless(in_array($access, ['all', 'free', 'premium'], true), 404);

        $this->accessFilter = $access;
        $this->resetPage();
    }
}
