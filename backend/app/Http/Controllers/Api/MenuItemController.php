<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles menu item CRUD with recipe ingredient management.
 */
class MenuItemController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista', 'kitchen'];
    protected array $mutableStaffRoles = ['manager', 'barista', 'kitchen'];

    protected array $searchable = ['name', 'sku'];

    protected array $with = ['restaurant', 'menuCategory', 'recipeIngredients.ingredient'];

    public function __construct(
        private readonly MenuService $menuService
    ) {}

    protected function modelClass(): string
    {
        return \App\Models\MenuItem::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'restaurant_id'            => ['sometimes', 'nullable', 'exists:restaurants,id'],
            'menu_category_id'         => ['nullable', 'exists:menu_categories,id'],
            'name'                     => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'sku'                      => ['nullable', 'string', 'max:100'],
            'description'              => ['nullable', 'string'],
            'price'                    => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'is_available'             => ['sometimes', 'boolean'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Create a menu item with recipe ingredients.
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $restaurantId = $this->resolveRestaurantContext($request);

        $validated = $request->validate($this->rules());
        $ingredients = $validated['ingredients'] ?? [];
        unset($validated['ingredients']);

        $record = $this->menuService->createMenuItem($validated, $restaurantId, $ingredients);
        $record->load($this->with);

        return ApiResponse::success($record, 'Created', 201);
    }

    /**
     * Update a menu item and its recipe ingredients.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $restaurantId = $this->resolveRestaurantContext($request);
        $record = $this->scopedQuery($request)->findOrFail($id);

        $validated = $request->validate($this->rules(true));
        $ingredients = $validated['ingredients'] ?? [];
        unset($validated['ingredients']);

        $record = $this->menuService->updateMenuItem($record, $validated, $restaurantId, $ingredients);
        $record->load($this->with);

        return ApiResponse::success($record, 'Updated');
    }
}
