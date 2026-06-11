<?php

namespace Database\Seeders;

use App\Models\DefaultRolePermission;
use Illuminate\Database\Seeder;

class DefaultPermissionsSeeder extends Seeder
{
    /**
     * All entities in the system.
     */
    private array $entities = [
        'users',
        'restaurants',
        'menu_categories',
        'menu_items',
        'orders',
        'order_items',
        'order_status_history',
        'preferences',
        'ingredients',
        'recipe_ingredients',
        'inventory_transactions',
        'dining_tables',
        'table_sessions',
        'shifts',
        'staff_shift_assignments',
        'payment_events',
        'role_permissions',
        'feedbacks',
        'kds_kitchen',
        'kds_barista',
        'kds_expeditor',
    ];

    public function run(): void
    {
        // Clear existing default permissions
        DefaultRolePermission::query()->truncate();

        $this->seedManager();
        $this->seedFloorManager();
        $this->seedCashier();
        $this->seedServer();
        $this->seedKitchen();
        $this->seedBarista();
        $this->seedHost();
        $this->seedInventory();
    }

    private function seedManager(): void
    {
        // Manager: can read AND write everything
        $permissions = array_map(fn($entity) => [
            'role_name'  => 'manager',
            'entity_key' => $entity,
            'can_read'   => true,
            'can_write'  => true,
        ], $this->entities);

        DefaultRolePermission::insert($permissions);
    }

    private function seedFloorManager(): void
    {
        $readWrite = [
            'orders', 'order_items', 'order_status_history',
            'dining_tables', 'table_sessions', 'shifts',
            'staff_shift_assignments',
            'kds_kitchen', 'kds_barista', 'kds_expeditor',
        ];

        $readOnly = ['menu_items', 'menu_categories', 'payment_events'];

        $noAccess = [
            'users', 'ingredients', 'recipe_ingredients',
            'inventory_transactions', 'preferences',
            'restaurants', 'role_permissions',
        ];

        $this->seedRole('floor_manager', $readWrite, $readOnly, $noAccess);
    }

    private function seedCashier(): void
    {
        $readWrite = [
            'orders', 'order_items', 'payment_events',
            'dining_tables', 'table_sessions',
        ];

        $readOnly = ['menu_items', 'menu_categories'];

        $noAccess = array_diff($this->entities, $readWrite, $readOnly);

        $this->seedRole('cashier', $readWrite, $readOnly, $noAccess);
    }

    private function seedServer(): void
    {
        $readWrite = [
            'orders', 'order_items', 'order_status_history',
            'table_sessions',
        ];

        $readOnly = [
            'menu_items', 'menu_categories', 'dining_tables',
            'kds_expeditor',
        ];

        $noAccess = array_diff($this->entities, $readWrite, $readOnly);

        $this->seedRole('server', $readWrite, $readOnly, $noAccess);
    }

    private function seedKitchen(): void
    {
        $readWrite = [
            'order_items', 'order_status_history',
            'ingredients', 'inventory_transactions',
            'kds_kitchen',
        ];

        $readOnly = ['orders', 'menu_items', 'recipe_ingredients'];

        $noAccess = array_diff($this->entities, $readWrite, $readOnly);

        $this->seedRole('kitchen', $readWrite, $readOnly, $noAccess);
    }

    private function seedBarista(): void
    {
        $readWrite = [
            'order_items', 'order_status_history',
            'ingredients', 'inventory_transactions',
            'kds_barista',
        ];

        $readOnly = ['orders', 'menu_items', 'recipe_ingredients'];

        $noAccess = array_diff($this->entities, $readWrite, $readOnly);

        $this->seedRole('barista', $readWrite, $readOnly, $noAccess);
    }

    private function seedHost(): void
    {
        $readWrite = ['dining_tables', 'table_sessions'];

        $readOnly = ['orders', 'menu_items', 'menu_categories'];

        $noAccess = array_diff($this->entities, $readWrite, $readOnly);

        $this->seedRole('host', $readWrite, $readOnly, $noAccess);
    }

    private function seedInventory(): void
    {
        $readWrite = [
            'ingredients', 'inventory_transactions',
            'recipe_ingredients',
        ];

        $readOnly = ['menu_items'];

        $noAccess = array_diff($this->entities, $readWrite, $readOnly);

        $this->seedRole('inventory', $readWrite, $readOnly, $noAccess);
    }

    /**
     * Helper to seed permissions for a role.
     */
    private function seedRole(string $role, array $readWrite, array $readOnly, array $noAccess): void
    {
        $permissions = [];

        foreach ($readWrite as $entity) {
            $permissions[] = [
                'role_name'  => $role,
                'entity_key' => $entity,
                'can_read'   => true,
                'can_write'  => true,
            ];
        }

        foreach ($readOnly as $entity) {
            $permissions[] = [
                'role_name'  => $role,
                'entity_key' => $entity,
                'can_read'   => true,
                'can_write'  => false,
            ];
        }

        foreach ($noAccess as $entity) {
            $permissions[] = [
                'role_name'  => $role,
                'entity_key' => $entity,
                'can_read'   => false,
                'can_write'  => false,
            ];
        }

        DefaultRolePermission::insert($permissions);
    }
}
