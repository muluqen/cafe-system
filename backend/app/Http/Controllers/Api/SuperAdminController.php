<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super Admin controller: platform management, restaurant approvals, notifications.
 */
class SuperAdminController extends Controller
{
    public function __construct(
        private readonly SuperAdminService $superAdminService
    ) {}

    /**
     * GET /admin/stats
     */
    public function stats(): JsonResponse
    {
        return ApiResponse::success($this->superAdminService->getPlatformStats());
    }

    /**
     * GET /admin/restaurants/pending
     */
    public function pendingRestaurants(): JsonResponse
    {
        return ApiResponse::success($this->superAdminService->getPendingRestaurants());
    }

    /**
     * GET /admin/restaurants
     */
    public function allRestaurants(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'per_page']);

        return ApiResponse::success($this->superAdminService->getAllRestaurants($filters));
    }

    /**
     * POST /admin/restaurants/{id}/approve
     */
    public function approveRestaurant(string $id): JsonResponse
    {
        $restaurant = $this->superAdminService->approveRestaurant(
            (int) $id,
            request()->user()
        );

        return ApiResponse::success($restaurant, 'Restaurant approved');
    }

    /**
     * POST /admin/restaurants/{id}/reject
     */
    public function rejectRestaurant(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $restaurant = $this->superAdminService->rejectRestaurant(
            (int) $id,
            $validated['reason'],
            $request->user()
        );

        return ApiResponse::success($restaurant, 'Restaurant rejected');
    }

    /**
     * POST /admin/restaurants/{id}/suspend
     */
    public function suspendRestaurant(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $restaurant = $this->superAdminService->suspendRestaurant(
            (int) $id,
            $validated['reason'],
            $request->user()
        );

        return ApiResponse::success($restaurant, 'Restaurant suspended');
    }

    /**
     * POST /admin/restaurants/{id}/reactivate
     */
    public function reactivateRestaurant(string $id): JsonResponse
    {
        $restaurant = $this->superAdminService->reactivateRestaurant(
            (int) $id,
            request()->user()
        );

        return ApiResponse::success($restaurant, 'Restaurant reactivated');
    }

    /**
     * GET /admin/users
     */
    public function allUsers(Request $request): JsonResponse
    {
        $filters = $request->only(['role', 'is_super_admin', 'restaurant_id', 'per_page']);

        if (isset($filters['is_super_admin'])) {
            $filters['is_super_admin'] = (bool) $filters['is_super_admin'];
        }

        return ApiResponse::success($this->superAdminService->getAllUsers($filters));
    }

    /**
     * GET /admin/notifications
     */
    public function notifications(): JsonResponse
    {
        return ApiResponse::success($this->superAdminService->getNotifications());
    }

    /**
     * POST /admin/notifications/{id}/read
     */
    public function markNotificationRead(string $id): JsonResponse
    {
        $notification = $this->superAdminService->markNotificationRead((int) $id);

        return ApiResponse::success($notification, 'Notification marked as read');
    }

    /**
     * GET /admin/analytics
     */
    public function platformAnalytics(Request $request): JsonResponse
    {
        $period = $request->query('period', 'week');

        if (!in_array($period, ['week', 'month'])) {
            $period = 'week';
        }

        return ApiResponse::success($this->superAdminService->getPlatformAnalytics($period));
    }
}
