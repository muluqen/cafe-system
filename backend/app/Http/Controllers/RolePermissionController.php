<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles role permission management.
 */
class RolePermissionController extends Controller
{
    /**
     * List role permissions for the restaurant.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) {
            return ApiResponse::success([]);
        }

        $permissions = RolePermission::where('restaurant_id', $restaurantId)->get();

        return ApiResponse::success($permissions);
    }

    /**
     * Create or update a role permission.
     */
    public function store(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $validated = $request->validate([
            'staff_role' => 'required|string',
            'entity_key' => 'required|string',
            'can_read'   => 'required|boolean',
            'can_write'  => 'required|boolean',
        ]);

        $permission = RolePermission::updateOrCreate(
            [
                'restaurant_id' => $restaurantId,
                'staff_role'    => $validated['staff_role'],
                'entity_key'    => $validated['entity_key'],
            ],
            [
                'can_read'  => $validated['can_read'],
                'can_write' => $validated['can_write'],
            ]
        );

        return ApiResponse::success($permission, 'Saved');
    }
}
