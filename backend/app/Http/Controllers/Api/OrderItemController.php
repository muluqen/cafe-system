<?php

namespace App\Http\Controllers\Api;

use App\Events\ItemStatusChanged;
use App\Http\Responses\ApiResponse;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles order item CRUD.
 */
class OrderItemController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'floor_manager', 'host', 'server', 'cashier', 'barista', 'kitchen'];
    protected array $mutableStaffRoles = ['manager', 'floor_manager', 'host', 'server', 'cashier', 'barista', 'kitchen'];
    protected bool $allowCustomerMutations = true;

    protected ?string $restaurantColumn = null;

    protected ?string $restaurantRelation = 'order';

    protected array $searchable = ['item_name'];

    protected array $with = ['order', 'menuItem'];

    public function __construct(
        private readonly OrderService $orderService
    ) {}

    protected function modelClass(): string
    {
        return OrderItem::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'order_id'         => [$updating ? 'sometimes' : 'required', 'exists:orders,id'],
            'menu_item_id'     => ['nullable', 'exists:menu_items,id'],
            'item_name'        => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'quantity'         => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0.01'],
            'unit_price'       => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'line_total'       => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string'],
            'routing_station'  => ['sometimes', 'string'],
            'status'           => ['sometimes', 'string'],
        ];
    }

    /**
     * Update an order item and auto-complete the order if all items are ready.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $record = $this->scopedQuery($request)->findOrFail($id);

        $oldStatus = $record->status;

        $validated = $request->validate($this->rules(true));
        $validated = $this->mutateValidated($validated, $request, null, true);
        $record->update($validated);
        $record->load($this->with);

        // Broadcast item status change to KDS
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            broadcast(new ItemStatusChanged($record, $oldStatus, $validated['status']));
        }

        // Auto-complete order if all items are ready
        if (isset($validated['status']) && $validated['status'] === 'ready') {
            $this->checkAndCompleteOrder($record->order);
        }

        return ApiResponse::success($record, 'Updated');
    }

    /**
     * Check if all items in an order are ready, and if so, mark the order as ready.
     */
    private function checkAndCompleteOrder($order): void
    {
        if (!$order || $order->status === 'ready' || $order->status === 'completed') {
            return;
        }

        $allReady = $order->orderItems->every(
            fn ($item) => $item->status === 'ready' || $item->status === 'completed'
        );

        if ($allReady) {
            $this->orderService->updateStatus($order, 'ready', $order->user_id, 'All items ready');
        }
    }

    /**
     * Scope queries to the customer's own orders.
     */
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
