<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    #[Scope]
    public function matchingSearch(Builder $query, ?string $search): void
    {
        if (blank($search)) {
            return;
        }

        $query->where('name', 'like', '%'.$search.'%');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Tag $tag): void {
            if ($tag->isDirty('name') || blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
