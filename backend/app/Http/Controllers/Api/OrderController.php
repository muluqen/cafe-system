<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];
    protected array $mutableStaffRoles = ['manager', 'cashier', 'barista'];
    protected bool $allowCustomerMutations = true;

    protected array $searchable = ['order_number', 'status'];

    protected array $with = ['restaurant', 'table', 'user', 'items.menuItem'];

    protected function modelClass(): string
    {
        return Order::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
            'table_id' => ['nullable', 'exists:tables,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'order_number' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'max:50'],
            'subtotal' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'total' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'placed_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
        ];
    }

    protected function mutateValidated(array $validated, Request $request, ?int $restaurantId, bool $updating = false): array
    {
        $validated = parent::mutateValidated($validated, $request, $restaurantId, $updating);

        if ($request->user()->role === 'customer') {
            $validated['user_id'] = $request->user()->id;
            $validated['status'] = $validated['status'] ?? 'pending';
        }

        if (!$updating && empty($validated['order_number'])) {
            $validated['order_number'] = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        }

        return $validated;
    }

    protected function scopedQuery(Request $request): Builder
    {
        $query = parent::scopedQuery($request);

        if ($request->user()->isCustomer()) {
            $query->where('user_id', $request->user()->id);
        }

        return $query;
    }
}
