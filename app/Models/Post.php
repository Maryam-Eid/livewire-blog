<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'title', 'slug', 'excerpt', 'content', 'featured_image', 'status', 'is_premium', 'published_at', 'views_count'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $casts = [
        'is_premium' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    #[Scope]
    public function published(Builder $query): void
    {
        $query
            ->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    public function dueForPublishing(Builder $query): void
    {
        $query
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    public function matchingSearch(Builder $query, ?string $search): void
    {
        if (blank($search)) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($search): void {
            $searchQuery
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('content', 'like', '%'.$search.'%')
                ->orWhere('excerpt', 'like', '%'.$search.'%');
        });
    }

    #[Scope]
    public function forAccess(Builder $query, ?string $access): void
    {
        match ($access) {
            'free' => $query->where('is_premium', false),
            'premium' => $query->where('is_premium', true),
            default => null,
        };
    }

    #[Scope]
    public function forCategory(Builder $query, ?string $slug): void
    {
        if (blank($slug)) {
            return;
        }

        $query->whereHas('categories', fn (Builder $categories) => $categories->where('slug', $slug));
    }

    /**
     * @param  list<string>  $slugs
     */
    #[Scope]
    public function forTags(Builder $query, array $slugs): void
    {
        foreach (array_filter($slugs) as $slug) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->where('slug', $slug));
        }
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function canBeEditedBy(?User $user): bool
    {
        return $user !== null
            && $user->can('create-post')
            && ($user->can('edit-any-post') || ($user->can('edit-post') && $this->user_id === $user->id));
    }

    public function featuredImageUrl(): ?string
    {
        if (blank($this->featured_image)) {
            return null;
        }

        if ($this->isFeaturedImageUrl()) {
            return $this->featured_image;
        }

        return Storage::url($this->featured_image);
    }

    public function isFeaturedImageUrl(): bool
    {
        if (blank($this->featured_image)) {
            return false;
        }

        return Str::startsWith($this->featured_image, ['http://', 'https://']);
    }

    public function applyFeaturedImage(UploadedFile|string|null $image): void
    {
        if ($image instanceof UploadedFile) {
            $this->deleteStoredFeaturedImage();
            $this->featured_image = $image->store('posts', 'public');

            return;
        }

        if ($image === $this->featured_image) {
            return;
        }

        $this->deleteStoredFeaturedImage();
        $this->featured_image = $image;
    }

    private function deleteStoredFeaturedImage(): void
    {
        if (filled($this->featured_image) && ! $this->isFeaturedImageUrl()) {
            Storage::disk('public')->delete($this->featured_image);
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Post $post): void {
            if (blank($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }
}
