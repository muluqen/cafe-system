<?php

namespace App\Http\Controllers\Api;

use App\Models\StaffShiftAssignment;

class StaffShiftAssignmentController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];

    protected ?string $restaurantColumn = null;

    protected ?string $restaurantRelation = 'shift';

    protected array $searchable = ['role', 'status'];

    protected array $with = ['shift', 'user'];

    protected function modelClass(): string
    {
        return StaffShiftAssignment::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'shift_id' => [$updating ? 'sometimes' : 'required', 'exists:shifts,id'],
            'user_id' => [$updating ? 'sometimes' : 'required', 'exists:users,id'],
            'role' => ['nullable', 'string', 'max:80'],
            'status' => ['sometimes', 'string', 'max:40'],
            'clock_in_at' => ['nullable', 'date'],
            'clock_out_at' => ['nullable', 'date'],
        ];
    }
}
