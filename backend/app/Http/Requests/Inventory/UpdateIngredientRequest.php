<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates ingredient update request data.
 */
class UpdateIngredientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'string', 'max:255'],
            'unit'           => ['sometimes', 'string', 'max:40'],
            'current_stock'  => ['sometimes', 'numeric', 'min:0'],
            'reorder_level'  => ['sometimes', 'numeric', 'min:0'],
            'cost_per_unit'  => ['nullable', 'numeric', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
