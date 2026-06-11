<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DefaultRolePermission;
use App\Services\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RbacController extends Controller
{
    public function __construct(
        private readonly RbacService $rbacService
    ) {}

    /**
     * GET /api/v1/rbac/defaults/{role}
     * Returns default permissions for a built-in role.
     */
    public function getDefaults(string $role): JsonResponse
    {
        $validRoles = [
            'manager', 'floor_manager', 'cashier', 'server',
            'kitchen', 'barista', 'host', 'inventory',
        ];

        if (!in_array($role, $validRoles)) {
            return ApiResponse::error('Invalid role name.', 422);
        }

        $permissions = $this->rbacService->getDefaultPermissions($role);

        return ApiResponse::success([
            'role'        => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * PUT /api/v1/rbac/defaults/{role}
     * Updates default permissions for a built-in role.
     */
    public function updateBuiltinRolePermissions(Request $request, string $role): JsonResponse
    {
        $validRoles = [
            'manager', 'floor_manager', 'cashier', 'server',
            'kitchen', 'barista', 'host', 'inventory',
        ];

        if (!in_array($role, $validRoles)) {
            return ApiResponse::error('Invalid role name.', 422);
        }

        $request->validate([
            'permissions'                             => 'required|array',
            'permissions.*.entity_key'                => 'required|string',
            'permissions.*.can_read'                  => 'required|boolean',
            'permissions.*.can_write'                 => 'required|boolean',
        ]);

        $permissions = $request->input('permissions', []);

        foreach ($permissions as $perm) {
            DefaultRolePermission::updateOrCreate(
                ['role_name' => $role, 'entity_key' => $perm['entity_key']],
                ['can_read' => $perm['can_read'], 'can_write' => $perm['can_write']]
            );
        }

        return ApiResponse::success([], 'Role permissions updated');
    }

    /**
     * GET /api/v1/rbac/my-permissions
     * Returns effective permissions for the authenticated user.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $this->rbacService->getEffectivePermissions($user);

        return ApiResponse::success([
            'user_id'     => $user->id,
            'staff_role'  => $user->staff_role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * GET /api/v1/rbac/custom-roles
     * Returns all custom roles for the authenticated user's restaurant.
     */
    public function getCustomRoles(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;

        if (!$restaurantId) {
            return ApiResponse::error('No restaurant associated with this account.', 403);
        }

        $roles = $this->rbacService->getCustomRoles($restaurantId);

        return ApiResponse::success($roles);
    }

    /**
     * POST /api/v1/rbac/custom-roles
     * Creates a new custom role.
     */
    public function createCustomRole(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'based_on'    => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*.entity_key' => 'required|string',
            'permissions.*.can_read'   => 'required|boolean',
            'permissions.*.can_write'  => 'required|boolean',
        ]);

        $role = $this->rbacService->createCustomRole(
            $request->only(['name', 'description', 'based_on', 'permissions']),
            $request->user()
        );

        return ApiResponse::success($role, 'Custom role created', 201);
    }

    /**
     * PUT /api/v1/rbac/custom-roles/{id}
     * Updates a custom role's name/description.
     */
    public function updateCustomRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role = $this->rbacService->updateCustomRole($id, $request->only(['name', 'description']));

        return ApiResponse::success($role, 'Custom role updated');
    }

    /**
     * PUT /api/v1/rbac/custom-roles/{id}/permissions
     * Updates permissions for a custom role.
     */
    public function updateCustomRolePermissions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permissions'                             => 'required|array',
            'permissions.*.entity_key'                => 'required|string',
            'permissions.*.can_read'                  => 'required|boolean',
            'permissions.*.can_write'                 => 'required|boolean',
        ]);

        $this->rbacService->updateCustomRolePermissions($id, $request->input('permissions'));

        return ApiResponse::success(null, 'Permissions updated');
    }

    /**
     * DELETE /api/v1/rbac/custom-roles/{id}
     * Soft-deletes a custom role (sets is_active = false).
     */
    public function deleteCustomRole(int $id): JsonResponse
    {
        try {
            $this->rbacService->deleteCustomRole($id);
            return ApiResponse::success(null, 'Custom role deactivated');
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }
    }

    /**
     * GET /api/v1/rbac/users/{userId}/permissions
     * Returns effective permissions + overrides for a specific user.
     */
    public function getUserPermissions(int $userId): JsonResponse
    {
        $result = $this->rbacService->getUserPermissions($userId);

        return ApiResponse::success($result);
    }

    /**
     * PUT /api/v1/rbac/users/{userId}/permissions
     * Sets per-user permission overrides.
     */
    public function setUserPermissions(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'permissions'                             => 'required|array',
            'permissions.*.entity_key'                => 'required|string',
            'permissions.*.can_read'                  => 'required|boolean',
            'permissions.*.can_write'                 => 'required|boolean',
        ]);

        $this->rbacService->setUserPermissionOverrides($userId, $request->input('permissions'));

        return ApiResponse::success(null, 'User permissions updated');
    }

    /**
     * DELETE /api/v1/rbac/users/{userId}/permissions/{entityKey}
     * Removes a specific per-user override (reverts to role default).
     */
    public function removeUserPermission(int $userId, string $entityKey): JsonResponse
    {
        $removed = $this->rbacService->removeUserPermissionOverride($userId, $entityKey);

        if ($removed) {
            return ApiResponse::success(null, 'Permission override removed');
        }

        return ApiResponse::error('No override found for this entity.', 404);
    }
}
