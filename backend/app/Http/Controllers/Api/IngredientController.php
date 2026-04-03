<?php

namespace App\Http\Controllers\Api;

use App\Models\Ingredient;

class IngredientController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];

    protected array $searchable = ['name', 'unit'];

    protected array $with = ['restaurant'];

    protected function modelClass(): string
    {
        return Ingredient::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'unit' => [$updating ? 'sometimes' : 'required', 'string', 'max:40'],
            'current_stock' => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
