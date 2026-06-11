<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\MenuCategory;
use Illuminate\Http\JsonResponse;

/**
 * Handles menu category CRUD.
 */
class MenuCategoryController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'barista', 'kitchen'];
    protected array $mutableStaffRoles = ['manager', 'barista', 'kitchen'];

    protected array $searchable = ['name'];

    protected array $with = ['restaurant'];

    protected function modelClass(): string
    {
        return MenuCategory::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id'  => ['sometimes', 'nullable', 'exists:restaurants,id'],
            'name'           => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'display_order'  => ['sometimes', 'integer', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
