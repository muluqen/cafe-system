<?php

namespace App\Services;

use App\Models\CustomRole;
use App\Models\CustomRolePermission;
use App\Models\DefaultRolePermission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Str;

class RbacService
{
    /**
     * Get default permissions for a built-in role.
     */
    public function getDefaultPermissions(string $role): array
    {
        $records = DefaultRolePermission::where('role_name', $role)->get();

        $permissions = [];
        foreach ($records as $record) {
            $permissions[] = [
                'entity_key' => $record->entity_key,
                'can_read'   => $record->can_read,
                'can_write'  => $record->can_write,
            ];
        }

        return $permissions;
    }

    /**
     * Get effective permissions for a user by merging all layers.
     */
    public function getEffectivePermissions(User $user): array
    {
        return $user->effectivePermissions();
    }

    /**
     * Check if a user can perform an action on an entity.
     */
    public function canAccess(User $user, string $entityKey, string $action): bool
    {
        $permissions = $this->getEffectivePermissions($user);

        if (!isset($permissions[$entityKey])) {
            return false;
        }

        $perm = $permissions[$entityKey];

        if ($action === 'read') {
            return (bool) $perm['can_read'];
        }

        if ($action === 'write') {
            return (bool) $perm['can_write'];
        }

        return false;
    }

    /**
     * Get all custom roles for a restaurant.
     */
    public function getCustomRoles(int $restaurantId): array
    {
        return CustomRole::where('restaurant_id', $restaurantId)
            ->with('permissions')
            ->get()
            ->toArray();
    }

    /**
     * Create a new custom role, optionally copying permissions from a base role.
     */
    public function createCustomRole(array $data, User $createdBy): CustomRole
    {
        $role = CustomRole::create([
            'restaurant_id' => $createdBy->restaurant_id,
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name']),
            'based_on'      => $data['based_on'] ?? null,
            'description'   => $data['description'] ?? null,
            'is_active'     => true,
            'created_by'    => $createdBy->id,
        ]);

        // If based_on is provided, copy permissions from that default role
        if (!empty($data['based_on'])) {
            $basePerms = DefaultRolePermission::where('role_name', $data['based_on'])->get();
            foreach ($basePerms as $bp) {
                CustomRolePermission::create([
                    'custom_role_id' => $role->id,
                    'entity_key'     => $bp->entity_key,
                    'can_read'       => $bp->can_read,
                    'can_write'      => $bp->can_write,
                ]);
            }
        }

        // If permissions array is provided directly, use that instead
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            // Clear any copied permissions first
            CustomRolePermission::where('custom_role_id', $role->id)->delete();

            foreach ($data['permissions'] as $perm) {
                CustomRolePermission::create([
                    'custom_role_id' => $role->id,
                    'entity_key'     => $perm['entity_key'],
                    'can_read'       => $perm['can_read'] ?? false,
                    'can_write'      => $perm['can_write'] ?? false,
                ]);
            }
        }

        return $role->load('permissions');
    }

    /**
     * Update a custom role's name/description.
     */
    public function updateCustomRole(int $customRoleId, array $data): CustomRole
    {
        $role = CustomRole::findOrFail($customRoleId);

        $role->update([
            'name'        => $data['name'] ?? $role->name,
            'description' => $data['description'] ?? $role->description,
        ]);

        if (!empty($data['name']) && $data['name'] !== $role->name) {
            $role->update(['slug' => Str::slug($data['name'])]);
        }

        return $role->load('permissions');
    }

    /**
     * Update all permissions for a custom role.
     */
    public function updateCustomRolePermissions(int $customRoleId, array $permissions): void
    {
        // Clear existing
        CustomRolePermission::where('custom_role_id', $customRoleId)->delete();

        // Insert new
        foreach ($permissions as $perm) {
            CustomRolePermission::create([
                'custom_role_id' => $customRoleId,
                'entity_key'     => $perm['entity_key'],
                'can_read'       => $perm['can_read'] ?? false,
                'can_write'      => $perm['can_write'] ?? false,
            ]);
        }
    }

    /**
     * Soft-delete a custom role (set is_active = false).
     */
    public function deleteCustomRole(int $customRoleId): bool
    {
        $role = CustomRole::findOrFail($customRoleId);

        // Check if any users are assigned this role
        $assignedCount = User::where('custom_role_id', $customRoleId)->count();
        if ($assignedCount > 0) {
            throw new \RuntimeException(
                "Cannot delete this role. {$assignedCount} user(s) are currently assigned to it."
            );
        }

        $role->update(['is_active' => false]);
        return true;
    }

    /**
     * Get per-user permission overrides.
     */
    public function getUserPermissionOverrides(int $userId): array
    {
        return RolePermission::where('user_id', $userId)
            ->get()
            ->map(fn($p) => [
                'entity_key' => $p->entity_key,
                'can_read'   => $p->can_read,
                'can_write'  => $p->can_write,
            ])
            ->toArray();
    }

    /**
     * Get full effective permissions for a user (including effective merge).
     */
    public function getUserPermissions(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'user_id'    => $user->id,
            'staff_role' => $user->staff_role,
            'custom_role_id' => $user->custom_role_id,
            'effective'  => $this->getEffectivePermissions($user),
            'overrides'  => $this->getUserPermissionOverrides($userId),
        ];
    }

    /**
     * Set per-user permission overrides (creates or updates RolePermission records).
     */
    public function setUserPermissionOverrides(int $userId, array $permissions): void
    {
        $user = User::findOrFail($userId);

        foreach ($permissions as $perm) {
            RolePermission::updateOrCreate(
                [
                    'user_id'     => $userId,
                    'entity_key'  => $perm['entity_key'],
                ],
                [
                    'restaurant_id' => $user->restaurant_id,
                    'staff_role'    => $user->staff_role,
                    'can_read'      => $perm['can_read'] ?? false,
                    'can_write'     => $perm['can_write'] ?? false,
                ]
            );
        }
    }

    /**
     * Remove a specific per-user permission override (reverts to role default).
     */
    public function removeUserPermissionOverride(int $userId, string $entityKey): bool
    {
        return RolePermission::where('user_id', $userId)
            ->where('entity_key', $entityKey)
            ->delete() > 0;
    }
}
