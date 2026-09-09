<?php

namespace App\Http\Requests\Api\Post;

use App\Http\Requests\Api\Post\Concerns\ValidatesPostWrite;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    use ValidatesPostWrite;

    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post && $post->canBeEditedBy($this->user());
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->postWriteRules(creating: false);
    }

    public function withValidator(Validator $validator): void
    {
        $this->ensureUniqueTitleSlug($validator);
        $this->ensureCanPublish($validator);
    }
}
