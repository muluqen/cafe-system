<?php

namespace App\Http\Controllers;

use App\Models\RecipeIngredient;
use Illuminate\Http\Request;

class RecipeIngredientController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) return response()->json([]);

        return RecipeIngredient::where('restaurant_id', $restaurantId)->get();
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) return response()->json(['message' => 'Unauthorized'], 403);

        $validated = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity_required' => 'required|numeric|min:0',
        ]);

        $recipe = RecipeIngredient::create([
            'restaurant_id' => $restaurantId,
            'menu_item_id' => $validated['menu_item_id'],
            'ingredient_id' => $validated['ingredient_id'],
            'quantity_required' => $validated['quantity_required'],
        ]);

        return response()->json($recipe);
    }

    public function update(Request $request, RecipeIngredient $recipeIngredient)
    {
        if ($recipeIngredient->restaurant_id !== $request->user()->restaurant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity_required' => 'required|numeric|min:0',
        ]);

        $recipeIngredient->update($validated);
        return response()->json($recipeIngredient);
    }

    public function destroy(Request $request, RecipeIngredient $recipeIngredient)
    {
        if ($recipeIngredient->restaurant_id !== $request->user()->restaurant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $recipeIngredient->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
