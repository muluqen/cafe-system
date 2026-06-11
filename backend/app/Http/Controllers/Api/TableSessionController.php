<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\TableSession;
use Illuminate\Http\JsonResponse;

/**
 * Handles table session CRUD.
 */
class TableSessionController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'floor_manager', 'host', 'server', 'cashier'];
    protected array $mutableStaffRoles = ['manager', 'floor_manager', 'host', 'server', 'cashier'];

    protected ?string $restaurantColumn = null;

    protected ?string $restaurantRelation = 'table';

    protected array $searchable = ['status'];

    protected array $with = ['table', 'order'];

    protected function modelClass(): string
    {
        return TableSession::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'table_id'     => [$updating ? 'sometimes' : 'required', 'exists:tables,id'],
            'order_id'     => ['nullable', 'exists:orders,id'],
            'opened_at'    => [$updating ? 'sometimes' : 'required', 'date'],
            'closed_at'    => ['nullable', 'date'],
            'guest_count'  => ['sometimes', 'integer', 'min:1'],
            'status'       => ['sometimes', 'string', 'max:40'],
        ];
    }
}
