<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RecipeIngredient;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        $user = $request->user();
        $restaurantId = $user->role === 'restaurant' 
            ? $user->restaurant_id 
            : (int) ($request->header('X-Restaurant-Id') ?: $request->input('restaurant_id'));

        if (!$restaurantId) return response()->json(['message' => 'Select a restaurant first to place an order.'], 403);

        $validated = $request->validate([
            'table_id' => 'nullable|exists:tables,id',
            'cart' => 'required|array',
            'subtotal' => 'required|numeric',
            'tax' => 'required|numeric',
            'total' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $request->user()->id,
                'table_id' => $validated['table_id'] ?? null,
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'status' => 'pending',
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'],
                'total' => $validated['total'],
                'notes' => 'POS Order',
                'placed_at' => Carbon::now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'changed_by' => $request->user()->id,
                'changed_at' => Carbon::now(),
                'note' => 'Order created via POS'
            ]);

            foreach ($validated['cart'] as $item) {
                // Enterprise Routing Engine
                $menuItem = \App\Models\MenuItem::with('menuCategory')->find($item['menu_item_id']);
                $catName = $menuItem && $menuItem->menuCategory ? strtolower($menuItem->menuCategory->name) : '';
                $isDrink = \Illuminate\Support\Str::contains($catName, ['coffee', 'drink', 'beverage', 'tea', 'latte', 'espresso']);
                $station = $isDrink ? 'barista' : 'kitchen';

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'item_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_total' => $item['price'] * $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                    'routing_station' => $station,
                    'status' => 'pending',
                ]);

                // The Thesis Automation Engine: Deduct Inventory based on Recipes!
                $recipes = RecipeIngredient::where('menu_item_id', $item['menu_item_id'])
                                           ->where('restaurant_id', $restaurantId)->get();

                foreach ($recipes as $recipe) {
                    $ingredient = Ingredient::find($recipe->ingredient_id);
                    if ($ingredient) {
                        $qtyToDeduct = $recipe->quantity_required * $item['quantity'];
                        $newStock = $ingredient->current_stock - $qtyToDeduct;

                        InventoryTransaction::create([
                            'ingredient_id' => $ingredient->id,
                            'type' => 'out',
                            'quantity' => $qtyToDeduct,
                            'balance_after' => $newStock,
                            'reference_type' => 'pos_order',
                            'reference_id' => $order->id,
                            'note' => 'Auto-deducted for order #' . $order->order_number,
                            'transacted_at' => Carbon::now(),
                        ]);

                        $ingredient->update(['current_stock' => $newStock]);
                    }
                }
            }

            DB::commit();
            return response()->json($order);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Checkout failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }
}
