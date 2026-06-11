<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Handles Kitchen Display System: order fetching per station, status updates.
 */
class KitchenService
{
    /**
     * Get orders for a specific station (kitchen or barista).
     *
     * @param  int    $restaurantId
     * @param  string $station
     * @return Collection
     */
    public function getOrdersForStation(int $restaurantId, string $station): Collection
    {
        return Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('items', function (Builder $builder) use ($station): void {
                $builder->where('routing_station', $station);
            })
            ->with(['items' => function (Builder $builder) use ($station): void {
                $builder->where('routing_station', $station);
            }])
            ->latest('placed_at')
            ->get();
    }

    /**
     * Update the status of a single order item on the KDS.
     *
     * @param  OrderItem $item
     * @param  string    $status
     * @return OrderItem
     */
    public function updateItemStatus(OrderItem $item, string $status): OrderItem
    {
        $item->update(['status' => $status]);

        $this->refreshOrderStatus($item->order_id);

        return $item;
    }

    /**
     * Refresh the parent order status based on its items.
     *
     * @param  int $orderId
     * @return void
     */
    private function refreshOrderStatus(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            return;
        }

        $totalItems = $order->items()->count();
        $completedItems = $order->items()->where('status', 'completed')->count();

        if ($totalItems > 0 && $completedItems === $totalItems) {
            $order->update(['status' => 'completed']);
        } elseif ($completedItems > 0) {
            $order->update(['status' => 'preparing']);
        }
    }
}
