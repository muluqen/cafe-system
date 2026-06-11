<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\StaffShiftAssignment;
use Illuminate\Http\JsonResponse;

/**
 * Handles staff shift assignment CRUD.
 */
class StaffShiftAssignmentController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant'];
    protected array $allowedStaffRoles = ['manager', 'floor_manager'];
    protected array $mutableStaffRoles = ['manager', 'floor_manager'];

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
            'shift_id'      => [$updating ? 'sometimes' : 'required', 'exists:shifts,id'],
            'user_id'       => [$updating ? 'sometimes' : 'required', 'exists:users,id'],
            'role'          => ['nullable', 'string', 'max:80'],
            'status'        => ['sometimes', 'string', 'max:40'],
            'clock_in_at'   => ['nullable', 'date'],
            'clock_out_at'  => ['nullable', 'date'],
        ];
    }
}
