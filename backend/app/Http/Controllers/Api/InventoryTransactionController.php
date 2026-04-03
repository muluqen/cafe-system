<?php

namespace App\Http\Controllers\Api;

use App\Models\InventoryTransaction;

class InventoryTransactionController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];

    protected array $searchable = ['type', 'reference_type', 'note'];

    protected array $with = ['ingredient', 'restaurant'];

    protected function modelClass(): string
    {
        return InventoryTransaction::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'ingredient_id' => [$updating ? 'sometimes' : 'required', 'exists:ingredients,id'],
            'restaurant_id' => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'type' => [$updating ? 'sometimes' : 'required', 'in:in,out,adjust'],
            'quantity' => [$updating ? 'sometimes' : 'required', 'numeric'],
            'balance_after' => ['nullable', 'numeric'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
            'transacted_at' => [$updating ? 'sometimes' : 'required', 'date'],
        ];
    }
}
