<?php

namespace App\Http\Controllers\Api;

use App\Models\Shift;

class ShiftController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];

    protected array $searchable = ['name', 'status', 'notes'];

    protected array $with = ['restaurant'];

    protected function modelClass(): string
    {
        return Shift::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:120'],
            'starts_at' => [$updating ? 'sometimes' : 'required', 'date'],
            'ends_at' => [$updating ? 'sometimes' : 'required', 'date'],
            'status' => ['sometimes', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
