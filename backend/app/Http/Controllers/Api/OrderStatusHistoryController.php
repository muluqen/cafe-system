<?php

namespace App\Http\Controllers\Api;

use App\Models\OrderStatusHistory;

class OrderStatusHistoryController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];
    protected array $mutableStaffRoles = ['manager', 'cashier', 'barista'];

    protected ?string $restaurantColumn = null;

    protected ?string $restaurantRelation = 'order';

    protected array $searchable = ['status', 'note'];

    protected array $with = ['order', 'changedBy'];

    protected function modelClass(): string
    {
        return OrderStatusHistory::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'order_id' => [$updating ? 'sometimes' : 'required', 'exists:orders,id'],
            'status' => [$updating ? 'sometimes' : 'required', 'string', 'max:50'],
            'changed_by' => ['nullable', 'exists:users,id'],
            'changed_at' => [$updating ? 'sometimes' : 'required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
