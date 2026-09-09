<?php

namespace App\Http\Requests\Api\Category;

use App\Http\Requests\Api\Category\Concerns\ValidatesCategoryWrite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use ValidatesCategoryWrite;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-roles') === true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->categoryWriteRules(creating: false);
    }
}
