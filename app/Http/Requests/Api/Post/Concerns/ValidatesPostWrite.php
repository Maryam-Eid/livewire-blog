<?php

namespace App\Http\Requests\Api\Post\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;

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
            'featured_image' => [...$sometimes, 'nullable', 'image', 'max:2048'],
            'status' => [...$sometimes, 'required', "in:{$statuses}"],
            'is_premium' => ['sometimes', 'boolean'],
            'scheduled_at' => ['required_if:status,scheduled', 'nullable', 'date', 'after:now'],
            'category_ids' => [...$sometimes, 'required', 'array', 'min:1'],
            'category_ids.*' => ['integer'],
            'tag_ids' => $creating
                ? ['nullable', 'array']
                : ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'Choose a future date and time.',
        ];
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
