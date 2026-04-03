<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuCategory;

class MenuCategoryController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];
    protected array $mutableStaffRoles = ['manager', 'barista'];

    protected array $searchable = ['name'];

    protected array $with = ['restaurant'];

    protected function modelClass(): string
    {
        return MenuCategory::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
