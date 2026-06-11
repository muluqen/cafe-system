<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\RecipeIngredient;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles recipe ingredient CRUD.
 */
class RecipeIngredientController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService
    ) {}

    /**
     * List recipe ingredients for the restaurant.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) {
            return ApiResponse::success([]);
        }

        $recipes = RecipeIngredient::where('restaurant_id', $restaurantId)->get();

        return ApiResponse::success($recipes);
    }

    /**
     * Create a recipe ingredient link.
     */
    public function store(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $validated = $request->validate([
            'menu_item_id'      => 'required|exists:menu_items,id',
            'ingredient_id'     => 'required|exists:ingredients,id',
            'quantity_required' => 'required|numeric|min:0',
        ]);

        $recipe = $this->menuService->createRecipeIngredient($validated, $restaurantId);

        return ApiResponse::success($recipe, 'Created', 201);
    }

    /**
     * Update a recipe ingredient.
     */
    public function update(Request $request, RecipeIngredient $recipeIngredient): JsonResponse
    {
        if ($recipeIngredient->restaurant_id !== $request->user()->restaurant_id) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $validated = $request->validate([
            'menu_item_id'      => 'required|exists:menu_items,id',
            'ingredient_id'     => 'required|exists:ingredients,id',
            'quantity_required' => 'required|numeric|min:0',
        ]);

        $recipe = $this->menuService->updateRecipeIngredient($recipeIngredient, $validated);

        return ApiResponse::success($recipe, 'Updated');
    }

    /**
     * Delete a recipe ingredient.
     */
    public function destroy(Request $request, RecipeIngredient $recipeIngredient): JsonResponse
    {
        if ($recipeIngredient->restaurant_id !== $request->user()->restaurant_id) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $recipeIngredient->delete();

        return ApiResponse::success(null, 'Deleted');
    }
}
