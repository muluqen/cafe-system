<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PlatformNotification;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin service: platform stats, restaurant approval, notifications, analytics.
 */
class SuperAdminService
{
    // ── Platform Stats ─────────────────────────────────────

    /**
     * Get platform-wide statistics.
     *
     * @return array<string, mixed>
     */
    public function getPlatformStats(): array
    {
        $totalRestaurants = Restaurant::count();
        $activeRestaurants = Restaurant::active()->count();
        $pendingRestaurants = Restaurant::pending()->count();
        $suspendedRestaurants = Restaurant::suspended()->count();

        $totalUsers = User::count();
        $totalOrdersToday = Order::whereDate('created_at', Carbon::today())->count();
        $totalOrdersThisMonth = Order::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $mostActiveRestaurants = Restaurant::withCount(['orders' => function ($q) {
            $q->whereMonth('created_at', Carbon::now()->month)
              ->whereYear('created_at', Carbon::now()->year);
        }])
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();

        return [
            'total_restaurants'     => $totalRestaurants,
            'active_restaurants'    => $activeRestaurants,
            'pending_restaurants'   => $pendingRestaurants,
            'suspended_restaurants' => $suspendedRestaurants,
            'total_users'           => $totalUsers,
            'total_orders_today'    => $totalOrdersToday,
            'total_orders_this_month' => $totalOrdersThisMonth,
            'most_active_restaurants' => $mostActiveRestaurants,
        ];
    }

    // ── Restaurant Approval ────────────────────────────────

    /**
     * Get all pending restaurants.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingRestaurants(): \Illuminate\Database\Eloquent\Collection
    {
        return Restaurant::pending()
            ->with('users')
            ->latest()
            ->get();
    }

    /**
     * Get all restaurants with filters.
     *
     * @param  array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function getAllRestaurants(array $filters = []): LengthAwarePaginator
    {
        $query = Restaurant::withCount('orders');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Approve a restaurant.
     *
     * @param  int  $restaurantId
     * @param  User $adminUser
     * @return Restaurant
     */
    public function approveRestaurant(int $restaurantId, User $adminUser): Restaurant
    {
        $restaurant = Restaurant::findOrFail($restaurantId);
        $restaurant->update([
            'status'      => 'active',
            'approved_at' => now(),
            'approved_by' => $adminUser->id,
            'is_active'   => true,
        ]);

        PlatformNotification::create([
            'type'  => 'restaurant_approved',
            'title' => 'Restaurant Approved',
            'body'  => "{$restaurant->name} has been approved and is now active.",
            'data'  => ['restaurant_id' => $restaurant->id, 'restaurant_name' => $restaurant->name],
        ]);

        return $restaurant;
    }

    /**
     * Reject a restaurant.
     *
     * @param  int       $restaurantId
     * @param  string    $reason
     * @param  User      $adminUser
     * @return Restaurant
     */
    public function rejectRestaurant(int $restaurantId, string $reason, User $adminUser): Restaurant
    {
        $restaurant = Restaurant::findOrFail($restaurantId);
        $restaurant->update([
            'status'           => 'suspended',
            'rejection_reason' => $reason,
        ]);

        PlatformNotification::create([
            'type'  => 'restaurant_rejected',
            'title' => 'Restaurant Rejected',
            'body'  => "{$restaurant->name} has been rejected. Reason: {$reason}",
            'data'  => ['restaurant_id' => $restaurant->id, 'reason' => $reason],
        ]);

        return $restaurant;
    }

    /**
     * Suspend a restaurant.
     *
     * @param  int       $restaurantId
     * @param  string    $reason
     * @param  User      $adminUser
     * @return Restaurant
     */
    public function suspendRestaurant(int $restaurantId, string $reason, User $adminUser): Restaurant
    {
        $restaurant = Restaurant::findOrFail($restaurantId);
        $restaurant->update([
            'status'           => 'suspended',
            'rejection_reason' => $reason,
            'is_active'        => false,
        ]);

        PlatformNotification::create([
            'type'  => 'restaurant_suspended',
            'title' => 'Restaurant Suspended',
            'body'  => "{$restaurant->name} has been suspended. Reason: {$reason}",
            'data'  => ['restaurant_id' => $restaurant->id, 'reason' => $reason],
        ]);

        return $restaurant;
    }

    /**
     * Reactivate a suspended restaurant.
     *
     * @param  int  $restaurantId
     * @param  User $adminUser
     * @return Restaurant
     */
    public function reactivateRestaurant(int $restaurantId, User $adminUser): Restaurant
    {
        $restaurant = Restaurant::findOrFail($restaurantId);
        $restaurant->update([
            'status'           => 'active',
            'rejection_reason' => null,
            'is_active'        => true,
        ]);

        PlatformNotification::create([
            'type'  => 'restaurant_reactivated',
            'title' => 'Restaurant Reactivated',
            'body'  => "{$restaurant->name} has been reactivated.",
            'data'  => ['restaurant_id' => $restaurant->id],
        ]);

        return $restaurant;
    }

    // ── Users ──────────────────────────────────────────────

    /**
     * Get all users with filters.
     *
     * @param  array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function getAllUsers(array $filters = []): LengthAwarePaginator
    {
        $query = User::with('restaurant');

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_super_admin'])) {
            $query->where('is_super_admin', $filters['is_super_admin']);
        }

        if (!empty($filters['restaurant_id'])) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    // ── Notifications ──────────────────────────────────────

    /**
     * Get all platform notifications.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotifications(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PlatformNotification::latest()->paginate(20);
    }

    /**
     * Mark a notification as read.
     *
     * @param  int $id
     * @return PlatformNotification
     */
    public function markNotificationRead(int $id): PlatformNotification
    {
        $notification = PlatformNotification::findOrFail($id);
        $notification->update(['read_at' => now()]);
        return $notification;
    }

    // ── Analytics ──────────────────────────────────────────

    /**
     * Get platform analytics for a given period.
     *
     * @param  string $period 'week' or 'month'
     * @return array<string, mixed>
     */
    public function getPlatformAnalytics(string $period = 'week'): array
    {
        $startDate = $period === 'month'
            ? Carbon::now()->startOfMonth()
            : Carbon::now()->startOfWeek();

        $endDate = now();

        $ordersPerDay = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenuePerDay = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $restaurantsPerDay = Restaurant::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topRestaurants = Restaurant::withCount('orders')
            ->orderByDesc('orders_count')
            ->take(10)
            ->get();

        return [
            'period'           => $period,
            'start_date'       => $startDate->toDateString(),
            'end_date'         => $endDate->toDateString(),
            'orders_per_day'   => $ordersPerDay,
            'revenue_per_day'  => $revenuePerDay,
            'restaurants_per_day' => $restaurantsPerDay,
            'top_restaurants'  => $topRestaurants,
        ];
    }
}
