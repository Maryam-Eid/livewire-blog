<?php

namespace App\Http\Requests\Api\Tag;

use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-roles') === true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'unique:tags,name,'.($tag instanceof Tag ? $tag->getKey() : ''),
            ],
        ];
    }
}
