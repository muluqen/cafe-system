<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates menu item update request data.
 */
class UpdateMenuItemRequest extends FormRequest
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
            'restaurant_id'              => ['sometimes', 'exists:restaurants,id'],
            'menu_category_id'           => ['nullable', 'exists:menu_categories,id'],
            'name'                       => ['sometimes', 'string', 'max:255'],
            'sku'                        => ['nullable', 'string', 'max:100'],
            'description'                => ['nullable', 'string'],
            'price'                      => ['sometimes', 'numeric', 'min:0'],
            'is_available'               => ['sometimes', 'boolean'],
            'preparation_time_minutes'   => ['nullable', 'integer', 'min:0'],
            'ingredients'                => ['nullable', 'array'],
            'ingredients.*.ingredient_id' => ['nullable', 'exists:ingredients,id'],
            'ingredients.*.name'         => ['nullable', 'string', 'max:255'],
            'ingredients.*.unit'         => ['nullable', 'string', 'max:40'],
            'ingredients.*.cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.quantity_required' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
