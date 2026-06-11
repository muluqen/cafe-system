<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\StaffShiftAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles shift creation, staff assignments, clock in/out.
 */
class ShiftService
{
    /**
     * List shifts for a restaurant.
     *
     * @param  int  $restaurantId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listShifts(int $restaurantId, int $perPage = 20): LengthAwarePaginator
    {
        return Shift::query()
            ->where('restaurant_id', $restaurantId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new shift.
     *
     * @param  array<string, mixed> $data
     * @return Shift
     */
    public function createShift(array $data): Shift
    {
        return Shift::query()->create($data);
    }

    /**
     * Update an existing shift.
     *
     * @param  Shift                $shift
     * @param  array<string, mixed> $data
     * @return Shift
     */
    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update($data);
        return $shift;
    }

    /**
     * List staff assignments for a shift.
     *
     * @param  int  $shiftId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listAssignments(int $shiftId, int $perPage = 20): LengthAwarePaginator
    {
        return StaffShiftAssignment::query()
            ->where('shift_id', $shiftId)
            ->with(['user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a staff assignment for a shift.
     *
     * @param  array<string, mixed> $data
     * @return StaffShiftAssignment
     */
    public function createAssignment(array $data): StaffShiftAssignment
    {
        return StaffShiftAssignment::query()->create($data);
    }

    /**
     * Update a staff assignment (e.g., clock in/out).
     *
     * @param  StaffShiftAssignment  $assignment
     * @param  array<string, mixed>  $data
     * @return StaffShiftAssignment
     */
    public function updateAssignment(StaffShiftAssignment $assignment, array $data): StaffShiftAssignment
    {
        $assignment->update($data);
        return $assignment;
    }
}
