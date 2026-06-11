<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles order listing, status updates, and routing.
 */
class OrderController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'floor_manager', 'host', 'server', 'cashier', 'barista', 'kitchen'];
    protected array $mutableStaffRoles = ['manager', 'floor_manager', 'host', 'server', 'cashier', 'barista', 'kitchen'];
    protected bool $allowCustomerMutations = true;
    protected bool $tenantScoped = true;

    protected array $searchable = ['order_number', 'status'];

    protected array $with = ['restaurant', 'table', 'user', 'orderItems', 'orderItems.menuItem', 'statusHistory'];

    public function __construct(
        private readonly OrderService $orderService
    ) {}

    protected function modelClass(): string
    {
        return \App\Models\Order::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
            'table_id'      => ['nullable', 'exists:tables,id'],
            'user_id'       => ['nullable', 'exists:users,id'],
            'order_number'  => ['nullable', 'string', 'max:50'],
            'status'        => ['sometimes', 'string', 'max:50'],
            'subtotal'      => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'tax'           => ['sometimes', 'numeric', 'min:0'],
            'total'         => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string'],
            'placed_at'     => ['nullable', 'date'],
            'closed_at'     => ['nullable', 'date'],
        ];
    }

    /**
     * List orders with support for comma-separated status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request);
        $query = $this->scopedQuery($request);

        // Handle comma-separated status filter (e.g. "pending,preparing")
        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $statuses = array_map('trim', explode(',', $status));
            $query->whereIn('status', $statuses);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '' && $this->searchable !== []) {
            $query->where(function ($builder) use ($search): void {
                foreach ($this->searchable as $column) {
                    $builder->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $data = $query->latest()->paginate(max($perPage, 1));

        return ApiResponse::success($data);
    }

    /**
     * Update order status with history tracking.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $record = $this->scopedQuery($request)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'note'   => ['nullable', 'string'],
        ]);

        $record = $this->orderService->updateStatus(
            $record,
            $validated['status'],
            $request->user()->id,
            $validated['note'] ?? ''
        );

        return ApiResponse::success($record, 'Updated');
    }

    /**
     * Scope queries to the customer's own orders.
     * Skips tenant scoping for customer users — they don't have a restaurant context.
     */
    protected function scopedQuery(Request $request): Builder
    {
        $model = $this->modelClass();
        $query = $model::query()->with($this->with);

        // For restaurant staff, apply tenant scoping
        if ($request->user()->isRestaurantStaff()) {
            $restaurantId = $this->resolveRestaurantContext($request);
            if ($restaurantId && $this->restaurantColumn) {
                $query->where($this->restaurantColumn, $restaurantId);
            }
        }

        // For customers, scope to their own orders
        if ($request->user()->isCustomer()) {
            $query->where('user_id', $request->user()->id);
        }

        return $query;
    }
}
