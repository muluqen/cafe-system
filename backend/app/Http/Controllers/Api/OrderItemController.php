<?php

namespace App\Http\Controllers\Api;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderItemController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];
    protected array $mutableStaffRoles = ['manager', 'cashier', 'barista'];
    protected bool $allowCustomerMutations = true;

    protected ?string $restaurantColumn = null;

    protected ?string $restaurantRelation = 'order';

    protected array $searchable = ['item_name'];

    protected array $with = ['order', 'menuItem'];

    protected function modelClass(): string
    {
        return OrderItem::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'order_id' => [$updating ? 'sometimes' : 'required', 'exists:orders,id'],
            'menu_item_id' => ['nullable', 'exists:menu_items,id'],
            'item_name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'quantity' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0.01'],
            'unit_price' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'line_total' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function scopedQuery(Request $request): Builder
    {
        $query = parent::scopedQuery($request);

        if ($request->user()->isCustomer()) {
            $query->whereHas('order', function (Builder $builder) use ($request): void {
                $builder->where('user_id', $request->user()->id);
            });
        }

        return $query;
    }
}
