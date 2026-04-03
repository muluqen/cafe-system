<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuItem;

class MenuItemController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];
    protected array $mutableStaffRoles = ['manager', 'barista'];

    protected array $searchable = ['name', 'sku'];

    protected array $with = ['restaurant', 'menuCategory'];

    protected function modelClass(): string
    {
        return MenuItem::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'menu_category_id' => ['nullable', 'exists:menu_categories,id'],
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
