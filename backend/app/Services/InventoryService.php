<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles ingredient management, inventory deduction, and low stock checks.
 */
class InventoryService
{
    /**
     * List ingredients for a restaurant.
     *
     * @param  int  $restaurantId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listIngredients(int $restaurantId, int $perPage = 20): LengthAwarePaginator
    {
        return Ingredient::query()
            ->where('restaurant_id', $restaurantId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Update an ingredient's stock or properties.
     *
     * @param  Ingredient           $ingredient
     * @param  array<string, mixed> $data
     * @return Ingredient
     */
    public function updateIngredient(Ingredient $ingredient, array $data): Ingredient
    {
        $oldStock = $ingredient->current_stock;
        $ingredient->update($data);

        if (isset($data['current_stock']) && (float) $data['current_stock'] !== (float) $oldStock) {
            $diff = (float) $data['current_stock'] - (float) $oldStock;
            $type = $diff > 0 ? 'in' : 'out';

            InventoryTransaction::query()->create([
                'ingredient_id'   => $ingredient->id,
                'restaurant_id'   => $ingredient->restaurant_id,
                'type'            => $type,
                'quantity'        => abs($diff),
                'balance_after'   => $data['current_stock'],
                'reference_type'  => 'manual_adjustment',
                'note'            => 'Manual stock adjustment',
                'transacted_at'   => now(),
            ]);
        }

        return $ingredient;
    }

    /**
     * List inventory transactions for a restaurant.
     *
     * @param  int  $restaurantId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listTransactions(int $restaurantId, int $perPage = 20): LengthAwarePaginator
    {
        return InventoryTransaction::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['ingredient'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Check ingredients that are at or below their reorder level.
     *
     * @param  int $restaurantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function lowStockCheck(int $restaurantId): \Illuminate\Database\Eloquent\Collection
    {
        return Ingredient::query()
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->get();
    }

    /**
     * Deduct stock for an ingredient and record the transaction.
     *
     * @param  Ingredient $ingredient
     * @param  float      $quantity
     * @param  int        $restaurantId
     * @param  string     $referenceType
     * @param  int|null   $referenceId
     * @param  string     $note
     * @return void
     */
    public function deductStock(Ingredient $ingredient, float $quantity, int $restaurantId, string $referenceType = 'order', ?int $referenceId = null, string $note = ''): void
    {
        $newStock = $ingredient->current_stock - $quantity;

        InventoryTransaction::query()->create([
            'ingredient_id'   => $ingredient->id,
            'restaurant_id'   => $restaurantId,
            'type'            => 'out',
            'quantity'        => $quantity,
            'balance_after'   => $newStock,
            'reference_type'  => $referenceType,
            'reference_id'    => $referenceId,
            'note'            => $note,
            'transacted_at'   => now(),
        ]);

        $ingredient->update(['current_stock' => $newStock]);
    }
}
