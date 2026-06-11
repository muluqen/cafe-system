<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\DB;

/**
 * Handles menu items, categories, and recipe ingredient management.
 */
class MenuService
{
    /**
     * Create a menu item with optional recipe ingredients.
     *
     * @param  array<string, mixed> $data
     * @param  int                  $restaurantId
     * @param  array<int, mixed>   $ingredients
     * @return MenuItem
     */
    public function createMenuItem(array $data, int $restaurantId, array $ingredients = []): MenuItem
    {
        $data['restaurant_id'] = $restaurantId;

        return DB::transaction(function () use ($data, $restaurantId, $ingredients): MenuItem {
            $menuItem = MenuItem::query()->create($data);

            $this->syncIngredients($menuItem, $ingredients, $restaurantId);

            return $menuItem;
        });
    }

    /**
     * Update a menu item and re-sync its recipe ingredients.
     *
     * @param  MenuItem            $menuItem
     * @param  array<string, mixed> $data
     * @param  int                  $restaurantId
     * @param  array<int, mixed>   $ingredients
     * @return MenuItem
     */
    public function updateMenuItem(MenuItem $menuItem, array $data, int $restaurantId, array $ingredients = []): MenuItem
    {
        return DB::transaction(function () use ($menuItem, $data, $restaurantId, $ingredients): MenuItem {
            $menuItem->update($data);

            if ($ingredients !== []) {
                $menuItem->recipeIngredients()->delete();
                $this->syncIngredients($menuItem, $ingredients, $restaurantId);
            }

            return $menuItem;
        });
    }

    /**
     * Find or create an ingredient and attach it to a menu item.
     *
     * @param  MenuItem $menuItem
     * @param  array    $ingredientsData
     * @param  int      $restaurantId
     * @return void
     */
    private function syncIngredients(MenuItem $menuItem, array $ingredientsData, int $restaurantId): void
    {
        foreach ($ingredientsData as $ing) {
            $ingredientId = $ing['ingredient_id'] ?? null;
            $ingredientName = trim($ing['name'] ?? '');

            $ingredient = null;

            if ($ingredientName === '' && $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
            } elseif ($ingredientName !== '') {
                $ingredient = Ingredient::firstOrCreate(
                    [
                        'restaurant_id' => $restaurantId,
                        'name'          => $ingredientName,
                    ],
                    [
                        'unit'           => $ing['unit'] ?? 'pcs',
                        'cost_per_unit'  => $ing['cost_per_unit'] ?? 0,
                        'current_stock'  => 100,
                        'reorder_level'  => 10,
                        'is_active'      => true,
                    ]
                );

                $ingredient->update(array_filter([
                    'cost_per_unit' => isset($ing['cost_per_unit']) ? (float) $ing['cost_per_unit'] : null,
                    'unit'          => $ing['unit'] ?? null,
                ], fn ($v) => !is_null($v)));
            } else {
                continue;
            }

            if ($ingredient) {
                $menuItem->recipeIngredients()->create([
                    'restaurant_id'       => $restaurantId,
                    'ingredient_id'       => $ingredient->id,
                    'quantity_required'   => $ing['quantity_required'] ?? 0.1,
                ]);
            }
        }
    }

    /**
     * Create a menu category.
     *
     * @param  array<string, mixed> $data
     * @return MenuCategory
     */
    public function createCategory(array $data): MenuCategory
    {
        return MenuCategory::query()->create($data);
    }

    /**
     * Update a menu category.
     *
     * @param  MenuCategory         $category
     * @param  array<string, mixed> $data
     * @return MenuCategory
     */
    public function updateCategory(MenuCategory $category, array $data): MenuCategory
    {
        $category->update($data);
        return $category;
    }

    /**
     * Create a recipe ingredient link.
     *
     * @param  array<string, mixed> $data
     * @param  int                  $restaurantId
     * @return RecipeIngredient
     */
    public function createRecipeIngredient(array $data, int $restaurantId): RecipeIngredient
    {
        $data['restaurant_id'] = $restaurantId;
        return RecipeIngredient::query()->create($data);
    }

    /**
     * Update a recipe ingredient link.
     *
     * @param  RecipeIngredient     $recipe
     * @param  array<string, mixed> $data
     * @return RecipeIngredient
     */
    public function updateRecipeIngredient(RecipeIngredient $recipe, array $data): RecipeIngredient
    {
        $recipe->update($data);
        return $recipe;
    }
}
