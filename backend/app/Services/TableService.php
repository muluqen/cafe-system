<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\TableSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles dining tables and table sessions.
 */
class TableService
{
    /**
     * List tables for a restaurant.
     *
     * @param  int  $restaurantId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listTables(int $restaurantId, int $perPage = 20): LengthAwarePaginator
    {
        return DiningTable::query()
            ->where('restaurant_id', $restaurantId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new dining table.
     *
     * @param  array<string, mixed> $data
     * @return DiningTable
     */
    public function createTable(array $data): DiningTable
    {
        return DiningTable::query()->create($data);
    }

    /**
     * Update an existing dining table.
     *
     * @param  DiningTable          $table
     * @param  array<string, mixed> $data
     * @return DiningTable
     */
    public function updateTable(DiningTable $table, array $data): DiningTable
    {
        $table->update($data);
        return $table;
    }

    /**
     * List table sessions for a restaurant.
     *
     * @param  int  $restaurantId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listSessions(int $restaurantId, int $perPage = 20): LengthAwarePaginator
    {
        return TableSession::query()
            ->whereHas('table', function ($builder) use ($restaurantId): void {
                $builder->where('restaurant_id', $restaurantId);
            })
            ->with(['table', 'order'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new table session.
     *
     * @param  array<string, mixed> $data
     * @return TableSession
     */
    public function createSession(array $data): TableSession
    {
        return TableSession::query()->create($data);
    }

    /**
     * Update a table session (e.g., close it).
     *
     * @param  TableSession         $session
     * @param  array<string, mixed> $data
     * @return TableSession
     */
    public function updateSession(TableSession $session, array $data): TableSession
    {
        $session->update($data);
        return $session;
    }
}
