<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles analytics: revenue, top items, peak hours, ingredient consumption.
 */
class AnalyticsService
{
    /**
     * Get revenue summary for a restaurant.
     *
     * @param  int    $restaurantId
     * @param  string $period
     * @return array
     */
    public function revenue(int $restaurantId, string $period = 'today'): array
    {
        $query = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNotIn('status', ['cancelled']);

        $query = $this->applyPeriodFilter($query, $period);

        $orders = $query->get();

        return [
            'total_revenue' => (float) $orders->sum('total'),
            'total_orders'  => $orders->count(),
            'average_order' => $orders->isNotEmpty() ? (float) $orders->avg('total') : 0,
            'period'        => $period,
        ];
    }

    /**
     * Get the top selling menu items.
     *
     * @param  int $restaurantId
     * @param  int $limit
     * @return Collection
     */
    public function topItems(int $restaurantId, int $limit = 10): Collection
    {
        return OrderItem::query()
            ->whereHas('order', function ($builder) use ($restaurantId): void {
                $builder->where('restaurant_id', $restaurantId)
                    ->whereNotIn('status', ['cancelled']);
            })
            ->select('item_name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(line_total) as total_revenue'))
            ->groupBy('item_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Get order volume by hour (peak hours).
     *
     * @param  int $restaurantId
     * @return Collection
     */
    public function peakHours(int $restaurantId): Collection
    {
        return Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNotIn('status', ['cancelled'])
            ->where('placed_at', '>=', Carbon::now()->subDays(30))
            ->select(DB::raw('EXTRACT(HOUR FROM placed_at) as hour'), DB::raw('COUNT(*) as order_count'))
            ->groupBy(DB::raw('EXTRACT(HOUR FROM placed_at)'))
            ->orderByDesc('order_count')
            ->get();
    }

    /**
     * Get ingredient consumption for a restaurant.
     *
     * @param  int $restaurantId
     * @return Collection
     */
    public function ingredientConsumption(int $restaurantId): Collection
    {
        return DB::table('inventory_transactions')
            ->join('ingredients', 'inventory_transactions.ingredient_id', '=', 'ingredients.id')
            ->where('inventory_transactions.restaurant_id', $restaurantId)
            ->where('inventory_transactions.type', 'out')
            ->select(
                'ingredients.name',
                'ingredients.unit',
                DB::raw('SUM(inventory_transactions.quantity) as total_consumed')
            )
            ->groupBy('ingredients.name', 'ingredients.unit')
            ->orderByDesc('total_consumed')
            ->get();
    }

    /**
     * Apply a period filter to an order query.
     *
     * @param  Builder $query
     * @param  string  $period
     * @return Builder
     */
    private function applyPeriodFilter($query, string $period): mixed
    {
        return match ($period) {
            'today'   => $query->whereDate('placed_at', Carbon::today()),
            'week'    => $query->where('placed_at', '>=', Carbon::now()->startOfWeek()),
            'month'   => $query->where('placed_at', '>=', Carbon::now()->startOfMonth()),
            'year'    => $query->where('placed_at', '>=', Carbon::now()->startOfYear()),
            default   => $query,
        };
    }
}
