<?php

namespace App\Http\Requests\Api\Post;

use App\Http\Requests\Api\Post\Concerns\ValidatesPostWrite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    use ValidatesPostWrite;

    public function authorize(): bool
    {
        return $this->user()?->can('create-post') === true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->postWriteRules(creating: true);
    }

    public function withValidator(Validator $validator): void
    {
        $this->ensureCanPublish($validator);
    }
}
