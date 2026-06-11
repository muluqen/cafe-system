<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles restaurant CRUD and public listing.
 */
class RestaurantController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];

    protected bool $tenantScoped = false;

    protected array $searchable = ['name', 'slug', 'email', 'phone'];

    public function __construct(
        private readonly RestaurantService $restaurantService
    ) {}

    protected function modelClass(): string
    {
        return \App\Models\Restaurant::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'name'      => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug'      => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * List active restaurants (public).
     */
    public function listPublic(): JsonResponse
    {
        return ApiResponse::success($this->restaurantService->listPublic());
    }

    /**
     * List restaurants scoped to the user.
     */
    public function index(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request);
        $perPage = (int) $request->query('per_page', $this->perPage);

        $data = $this->restaurantService->list($request->user(), $perPage);

        return ApiResponse::success($data);
    }

    /**
     * Show a single restaurant.
     */
    public function show(string $id): JsonResponse
    {
        $this->ensureRoleAllowed(request());

        $restaurant = $this->restaurantService->find($id);

        if (!$this->restaurantService->userHasAccess(request()->user(), $id)) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        return ApiResponse::success($restaurant);
    }

    /**
     * Store is disabled for restaurants.
     */
    public function store(Request $request): JsonResponse
    {
        return ApiResponse::error('Forbidden', null, 403);
    }

    /**
     * Update a restaurant.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'restaurant' || (int) $user->restaurant_id !== (int) $id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validated = $request->validate($this->rules(true));
        $restaurant = $this->restaurantService->update($id, $validated);

        return ApiResponse::success($restaurant, 'Updated');
    }

    /**
     * Delete is disabled.
     */
    public function destroy(string $id): JsonResponse
    {
        return ApiResponse::error('Forbidden', null, 403);
    }
}
