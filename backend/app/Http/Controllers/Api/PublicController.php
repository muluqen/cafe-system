<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ingredient;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

/**
 * Public endpoints for customer-facing pages (no auth required).
 */
class PublicController extends Controller
{
    /**
     * GET /api/v1/public/restaurants/{id}
     */
    public function restaurant(int $id): JsonResponse
    {
        $restaurant = Restaurant::where('id', $id)->active()->first();
        if (!$restaurant) {
            return ApiResponse::notFound('Restaurant not found.');
        }
        return ApiResponse::success($restaurant);
    }

    /**
     * GET /api/v1/public/restaurants/{id}/menu_items
     */
    public function menuItems(int $id): JsonResponse
    {
        $restaurant = Restaurant::where('id', $id)->active()->first();
        if (!$restaurant) {
            return ApiResponse::notFound('Restaurant not found.');
        }

        $items = MenuItem::where('restaurant_id', $id)
            ->where('is_available', true)
            ->with(['menuCategory', 'recipeIngredients.ingredient'])
            ->get();

        return ApiResponse::success($items);
    }

    /**
     * GET /api/v1/public/restaurants/{id}/menu_categories
     */
    public function menuCategories(int $id): JsonResponse
    {
        $restaurant = Restaurant::where('id', $id)->active()->first();
        if (!$restaurant) {
            return ApiResponse::notFound('Restaurant not found.');
        }

        $categories = MenuCategory::where('restaurant_id', $id)
            ->where('is_active', true)
            ->get();

        return ApiResponse::success($categories);
    }

    /**
     * GET /api/v1/public/restaurants/{id}/ingredients
     * Returns all active ingredients for build-your-own dishes.
     */
    public function ingredients(int $id): JsonResponse
    {
        $restaurant = Restaurant::where('id', $id)->active()->first();
        if (!$restaurant) {
            return ApiResponse::notFound('Restaurant not found.');
        }

        $ingredients = Ingredient::where('restaurant_id', $id)
            ->where('is_active', true)
            ->where('current_stock', '>', 0)
            ->get(['id', 'name', 'unit', 'cost_per_unit', 'calories_per_unit']);

        return ApiResponse::success($ingredients);
    }
}
