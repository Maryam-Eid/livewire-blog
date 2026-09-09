<?php

namespace App\Http\Requests\Api\Post;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tag' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
            'access' => ['sometimes', 'nullable', 'in:all,free,premium'],
        ];
    }

    /**
     * @return list<string>
     */
    public function tagSlugs(): array
    {
        return array_values(array_filter([
            $this->validated('tag'),
            ...($this->validated('tags') ?? []),
        ]));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('tags'))) {
            $this->merge([
                'tags' => array_values(array_filter(array_map('trim', explode(',', $this->string('tags')->value())))),
            ]);
        }
    }
}
