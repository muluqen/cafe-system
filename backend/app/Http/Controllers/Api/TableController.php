<?php

namespace App\Http\Controllers\Api;

use App\Models\DiningTable;

class TableController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];
    protected array $mutableStaffRoles = ['manager', 'cashier'];

    protected array $searchable = ['name', 'location', 'status'];

    protected array $with = ['restaurant'];

    protected function modelClass(): string
    {
        return DiningTable::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:100'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'string', 'max:40'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
