<?php

namespace App\Http\Requests\Api\Category\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ValidatesCategoryWrite
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function categoryWriteRules(bool $creating): array
    {
        $sometimes = $creating ? [] : ['sometimes'];
        $unique = Rule::unique('categories', 'name');

        if (! $creating) {
            $unique->ignore($this->route('category'));
        }

        return [
            'name' => [...$sometimes, 'required', 'string', 'min:2', 'max:255', $unique],
            'description' => [...$sometimes, 'nullable', 'string', 'max:1000'],
            'color' => [...$sometimes, 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
