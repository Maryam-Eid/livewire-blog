<?php

namespace App\Http\Requests\Api\Post\Concerns;

use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;

trait ValidatesPostWrite
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function postWriteRules(bool $creating): array
    {
        $sometimes = $creating ? [] : ['sometimes'];
        $statuses = $creating
            ? 'draft,scheduled,published'
            : 'draft,scheduled,published,archived';

        return [
            'title' => [...$sometimes, 'required', 'string', 'min:3', 'max:255'],
            'excerpt' => [...$sometimes, 'nullable', 'string', 'max:500'],
            'content' => [...$sometimes, 'required', 'string', 'min:10'],
            'featured_image' => [
                ...$sometimes,
                'nullable',
                ...($this->hasFile('featured_image')
                    ? ['image', 'max:2048']
                    : ['string', 'url', 'max:2048']),
            ],
            'status' => [...$sometimes, 'required', "in:{$statuses}"],
            'is_premium' => ['sometimes', 'boolean'],
            'scheduled_at' => ['required_if:status,scheduled', 'nullable', 'date', 'after:now'],
            'category_ids' => [...$sometimes, 'required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => $creating
                ? ['nullable', 'array']
                : ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'Choose a future date and time.',
        ];
    }

    protected function ensureUniqueTitleSlug(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            if ($validator->errors()->has('title') || ! $this->filled('title')) {
                return;
            }

            $post = $this->route('post');

            $exists = Post::query()
                ->where('slug', Str::slug($this->string('title')->value()))
                ->when($post instanceof Post, fn ($query) => $query->whereKeyNot($post->getKey()))
                ->exists();

            if ($exists) {
                $validator->errors()->add('title', 'A post with this title already exists.');
            }
        });
    }

    protected function ensureCanPublish(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $status = $this->input('status', $this->route('post')?->status);

            if (in_array($status, ['scheduled', 'published', 'archived'], true)
                && ! $this->user()?->can('publish-post')) {
                abort(403);
            }
        });
    }
}
