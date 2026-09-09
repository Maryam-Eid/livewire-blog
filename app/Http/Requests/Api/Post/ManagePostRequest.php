<?php

namespace App\Http\Requests\Api\Post;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ManagePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-post') === true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:all,draft,scheduled,published,archived'],
            'access' => ['sometimes', 'nullable', 'in:all,free,premium'],
        ];
    }
}
