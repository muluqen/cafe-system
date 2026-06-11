<?php

namespace App\Services;

use App\Events\NewOrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\RecipeIngredient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles order creation, status updates, checkout, and order routing.
 */
class OrderService
{
    /**
     * Check if all cart items have sufficient inventory.
     *
     * @param  int   $restaurantId
     * @param  array $cart
     * @return array{valid: bool, insufficient: array}
     */
    public function checkInventory(int $restaurantId, array $cart): array
    {
        $insufficient = [];

        foreach ($cart as $item) {
            $customizedIngredients = $item['customized_ingredients'] ?? null;
            $removedIngredients = $item['removed_ingredients'] ?? [];
            $requiredQty = $item['quantity'];

            if (is_array($customizedIngredients)) {
                foreach ($customizedIngredients as $customIng) {
                    $ingredient = Ingredient::find($customIng['ingredient_id']);
                    if ($ingredient && !in_array($ingredient->name, $removedIngredients)) {
                        $qtyNeeded = $customIng['quantity_required'] * $requiredQty;
                        if ($qtyNeeded > $ingredient->current_stock) {
                            $insufficient[] = [
                                'ingredient_id'   => $ingredient->id,
                                'ingredient_name' => $ingredient->name,
                                'required'        => $qtyNeeded,
                                'available'       => $ingredient->current_stock,
                                'item_name'       => $item['name'] ?? 'Unknown',
                            ];
                        }
                    }
                }
            } else {
                $recipes = RecipeIngredient::where('menu_item_id', $item['menu_item_id'])
                    ->where('restaurant_id', $restaurantId)
                    ->get();

                foreach ($recipes as $recipe) {
                    $ingredient = Ingredient::find($recipe->ingredient_id);
                    if ($ingredient && !in_array($ingredient->name, $removedIngredients)) {
                        $qtyNeeded = $recipe->quantity_required * $requiredQty;
                        if ($qtyNeeded > $ingredient->current_stock) {
                            $insufficient[] = [
                                'ingredient_id'   => $ingredient->id,
                                'ingredient_name' => $ingredient->name,
                                'required'        => $qtyNeeded,
                                'available'       => $ingredient->current_stock,
                                'item_name'       => $item['name'] ?? 'Unknown',
                            ];
                        }
                    }
                }
            }
        }

        return [
            'valid'       => empty($insufficient),
            'insufficient' => $insufficient,
        ];
    }

    /**
     * Create a new order with items from a cart.
     *
     * @param  int   $restaurantId
     * @param  int   $userId
     * @param  array $validated
     * @return Order
     * @throws ValidationException
     */
    public function createOrder(int $restaurantId, int $userId, array $validated): Order
    {
        $check = $this->checkInventory($restaurantId, $validated['cart']);
        if (!$check['valid']) {
            // Group by item for better UX
            $unavailableItems = [];
            foreach ($check['insufficient'] as $insufficient) {
                $itemName = $insufficient['item_name'];
                if (!isset($unavailableItems[$itemName])) {
                    $unavailableItems[$itemName] = [
                        'item_name' => $itemName,
                        'missing_ingredients' => [],
                    ];
                }
                $unavailableItems[$itemName]['missing_ingredients'][] = [
                    'ingredient' => $insufficient['ingredient_name'],
                    'required' => $insufficient['required'],
                    'available' => $insufficient['available'],
                ];
            }

            throw ValidationException::withMessages([
                'unavailable_items' => ['Some items are unavailable due to low stock'],
                'items' => array_values($unavailableItems),
            ]);
        }

        return DB::transaction(function () use ($restaurantId, $userId, $validated) {
            $order = Order::query()->create([
                'restaurant_id' => $restaurantId,
                'user_id'       => $userId,
                'table_id'      => $validated['table_id'] ?? null,
                'order_number'  => 'ORD-' . strtoupper(Str::random(6)),
                'status'        => 'pending',
                'subtotal'      => $validated['subtotal'],
                'tax'           => $validated['tax'],
                'total'         => $validated['total'],
                'notes'         => 'POS Order',
                'placed_at'     => Carbon::now(),
            ]);

            OrderStatusHistory::create([
                'order_id'    => $order->id,
                'status'      => 'pending',
                'changed_by'  => $userId,
                'changed_at'  => Carbon::now(),
                'note'        => 'Order created',
            ]);

            foreach ($validated['cart'] as $item) {
                $this->createOrderItem($order, $item, $restaurantId);
                $this->deductInventory($order, $item, $restaurantId);
            }

            broadcast(new NewOrderPlaced($order));

            return $order;
        });
    }

    /**
     * Update the status of an order and record history.
     *
     * @param  Order  $order
     * @param  string $status
     * @param  int    $changedBy
     * @param  string $note
     * @return Order
     */
    public function updateStatus(Order $order, string $status, int $changedBy, string $note = ''): Order
    {
        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        if ($status === 'completed' || $status === 'cancelled') {
            $order->update(['closed_at' => Carbon::now()]);
        }

        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'status'      => $status,
            'changed_by'  => $changedBy,
            'changed_at'  => Carbon::now(),
            'note'        => $note,
        ]);

        broadcast(new OrderStatusChanged($order, $oldStatus, $status));

        return $order;
    }

    /**
     * Determine the routing station for a cart item.
     *
     * @param  int|null $menuItemId
     * @return string
     */
    public function resolveStation(?int $menuItemId): string
    {
        if (!$menuItemId) {
            return 'kitchen';
        }

        $menuItem = MenuItem::with('menuCategory')->find($menuItemId);
        $catName = $menuItem && $menuItem->menuCategory
            ? strtolower($menuItem->menuCategory->name)
            : '';

        $isDrink = Str::contains($catName, ['coffee', 'drink', 'beverage', 'tea', 'latte', 'espresso', 'juice', 'smoothie']);

        return $isDrink ? 'barista' : 'kitchen';
    }

    /**
     * Create a single order item.
     *
     * @param  Order $order
     * @param  array $item
     * @param  int   $restaurantId
     * @return OrderItem
     */
    private function createOrderItem(Order $order, array $item, int $restaurantId): OrderItem
    {
        $station = $this->resolveStation($item['menu_item_id'] ?? null);

        return OrderItem::query()->create([
            'order_id'                 => $order->id,
            'menu_item_id'             => $item['menu_item_id'] ?? null,
            'item_name'                => $item['name'],
            'quantity'                 => $item['quantity'],
            'unit_price'               => $item['price'],
            'line_total'               => $item['price'] * $item['quantity'],
            'notes'                    => $item['notes'] ?? null,
            'customized_ingredients'   => $item['customized_ingredients'] ?? null,
            'routing_station'          => $station,
            'status'                   => 'pending',
        ]);
    }

    /**
     * Deduct inventory based on recipe or custom ingredients.
     *
     * @param  Order $order
     * @param  array $item
     * @param  int   $restaurantId
     * @return void
     */
    private function deductInventory(Order $order, array $item, int $restaurantId): void
    {
        $customizedIngredients = $item['customized_ingredients'] ?? null;
        $removedIngredients = $item['removed_ingredients'] ?? [];

        if (is_array($customizedIngredients)) {
            foreach ($customizedIngredients as $customIng) {
                $ingredient = Ingredient::find($customIng['ingredient_id']);
                if ($ingredient && !in_array($ingredient->name, $removedIngredients)) {
                    $qtyToDeduct = $customIng['quantity_required'] * $item['quantity'];
                    if ($qtyToDeduct <= 0) {
                        continue;
                    }
                    $this->recordDeduction($ingredient, $qtyToDeduct, $restaurantId, $order, 'Customized recipe');
                }
            }
        } else {
            $recipes = RecipeIngredient::where('menu_item_id', $item['menu_item_id'])
                ->where('restaurant_id', $restaurantId)
                ->get();

            foreach ($recipes as $recipe) {
                $ingredient = Ingredient::find($recipe->ingredient_id);
                if ($ingredient && !in_array($ingredient->name, $removedIngredients)) {
                    $qtyToDeduct = $recipe->quantity_required * $item['quantity'];
                    if ($qtyToDeduct <= 0) {
                        continue;
                    }
                    $this->recordDeduction($ingredient, $qtyToDeduct, $restaurantId, $order, 'Default recipe');
                }
            }
        }
    }

    /**
     * Record a single inventory deduction.
     *
     * @param  Ingredient $ingredient
     * @param  float      $qtyToDeduct
     * @param  int        $restaurantId
     * @param  Order      $order
     * @param  string     $label
     * @return void
     */
    private function recordDeduction(Ingredient $ingredient, float $qtyToDeduct, int $restaurantId, Order $order, string $label): void
    {
        $newStock = $ingredient->current_stock - $qtyToDeduct;
        
        // Prevent negative inventory - cap at zero
        if ($newStock < 0) {
            $qtyToDeduct = $ingredient->current_stock;
            $newStock = 0;
        }

        InventoryTransaction::query()->create([
            'ingredient_id'   => $ingredient->id,
            'restaurant_id'   => $restaurantId,
            'type'            => 'out',
            'quantity'        => $qtyToDeduct,
            'balance_after'   => $newStock,
            'reference_type'  => 'pos_order',
            'reference_id'    => $order->id,
            'note'            => "{$label} auto-deducted for order #{$order->order_number}",
            'transacted_at'   => Carbon::now(),
        ]);

        $ingredient->update(['current_stock' => $newStock]);
    }
}
