<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'color'])]
class Category extends Model
{
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    protected static function boot(){
        parent::boot();

        static::creating(function($category){
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
