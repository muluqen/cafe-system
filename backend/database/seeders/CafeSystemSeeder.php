<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentEvent;
use App\Models\Preference;
use App\Models\Restaurant;
use App\Models\Shift;
use App\Models\StaffShiftAssignment;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CafeSystemSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = collect([
            [
                'name' => 'Cafe Aurora Downtown',
                'slug' => 'cafe-aurora-downtown',
                'phone' => '+1-555-1000',
                'email' => 'downtown@cafeaurora.local',
                'address' => '123 Main St',
                'is_active' => true,
            ],
            [
                'name' => 'Cafe Aurora Riverside',
                'slug' => 'cafe-aurora-riverside',
                'phone' => '+1-555-2000',
                'email' => 'riverside@cafeaurora.local',
                'address' => '42 River Ave',
                'is_active' => true,
            ],
        ])->map(fn (array $restaurant) => Restaurant::query()->create($restaurant));

        $staffUsers = collect([
            ['name' => 'Downtown Manager', 'email' => 'downtown.manager@cafesystem.local', 'restaurant_idx' => 0, 'staff_role' => 'manager'],
            ['name' => 'Downtown Cashier', 'email' => 'downtown.cashier@cafesystem.local', 'restaurant_idx' => 0, 'staff_role' => 'cashier'],
            ['name' => 'Downtown Barista', 'email' => 'downtown.barista@cafesystem.local', 'restaurant_idx' => 0, 'staff_role' => 'barista'],
            ['name' => 'Riverside Manager', 'email' => 'riverside.manager@cafesystem.local', 'restaurant_idx' => 1, 'staff_role' => 'manager'],
            ['name' => 'Riverside Cashier', 'email' => 'riverside.cashier@cafesystem.local', 'restaurant_idx' => 1, 'staff_role' => 'cashier'],
            ['name' => 'Riverside Barista', 'email' => 'riverside.barista@cafesystem.local', 'restaurant_idx' => 1, 'staff_role' => 'barista'],
        ])->map(function (array $user) use ($restaurants) {
            return User::query()->create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password123'),
                'role' => 'restaurant',
                'staff_role' => $user['staff_role'],
                'restaurant_id' => $restaurants[$user['restaurant_idx']]->id,
            ]);
        });

        $customerUsers = collect([
            ['name' => 'Customer One', 'email' => 'customer1@cafesystem.local'],
            ['name' => 'Customer Two', 'email' => 'customer2@cafesystem.local'],
            ['name' => 'Customer Three', 'email' => 'customer3@cafesystem.local'],
        ])->map(fn (array $user) => User::query()->create([
            ...$user,
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'restaurant_id' => null,
        ]));

        $users = $staffUsers->concat($customerUsers);

        foreach ($users as $user) {
            Preference::query()->create([
                'user_id' => $user->id,
                'key' => 'theme',
                'value' => Arr::random(['light', 'warm', 'classic']),
            ]);
            Preference::query()->create([
                'user_id' => $user->id,
                'key' => 'language',
                'value' => 'en',
            ]);
        }

        foreach ($restaurants as $restaurant) {
            $categories = collect([
                ['name' => 'Coffee', 'display_order' => 1],
                ['name' => 'Pastries', 'display_order' => 2],
                ['name' => 'Sandwiches', 'display_order' => 3],
            ])->map(fn (array $category) => MenuCategory::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $category['name'],
                'display_order' => $category['display_order'],
                'is_active' => true,
            ]));

            $menuItems = collect([
                ['category' => 'Coffee', 'name' => 'Espresso', 'price' => 3.50, 'prep' => 3],
                ['category' => 'Coffee', 'name' => 'Latte', 'price' => 4.75, 'prep' => 5],
                ['category' => 'Coffee', 'name' => 'Cold Brew', 'price' => 4.25, 'prep' => 4],
                ['category' => 'Pastries', 'name' => 'Butter Croissant', 'price' => 3.25, 'prep' => 2],
                ['category' => 'Pastries', 'name' => 'Blueberry Muffin', 'price' => 3.10, 'prep' => 2],
                ['category' => 'Sandwiches', 'name' => 'Turkey Club', 'price' => 8.90, 'prep' => 8],
                ['category' => 'Sandwiches', 'name' => 'Caprese Panini', 'price' => 8.50, 'prep' => 7],
            ])->map(function (array $item) use ($categories, $restaurant) {
                $category = $categories->firstWhere('name', $item['category']);

                return MenuItem::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'menu_category_id' => $category?->id,
                    'name' => $item['name'],
                    'sku' => Str::upper(Str::slug($restaurant->slug . '-' . $item['name'])),
                    'description' => $item['name'] . ' - house favorite.',
                    'price' => $item['price'],
                    'is_available' => true,
                    'preparation_time_minutes' => $item['prep'],
                ]);
            });

            $ingredients = collect([
                ['name' => 'Coffee Beans', 'unit' => 'kg', 'stock' => 24.500, 'reorder' => 8.000, 'cost' => 18.40],
                ['name' => 'Milk', 'unit' => 'l', 'stock' => 60.000, 'reorder' => 20.000, 'cost' => 1.75],
                ['name' => 'Flour', 'unit' => 'kg', 'stock' => 40.000, 'reorder' => 12.000, 'cost' => 1.20],
                ['name' => 'Turkey Slices', 'unit' => 'kg', 'stock' => 16.000, 'reorder' => 5.000, 'cost' => 9.35],
                ['name' => 'Tomatoes', 'unit' => 'kg', 'stock' => 18.000, 'reorder' => 6.000, 'cost' => 2.40],
            ])->map(fn (array $ingredient) => Ingredient::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $ingredient['name'],
                'unit' => $ingredient['unit'],
                'current_stock' => $ingredient['stock'],
                'reorder_level' => $ingredient['reorder'],
                'cost_per_unit' => $ingredient['cost'],
                'is_active' => true,
            ]));

            foreach ($ingredients as $ingredient) {
                $inQty = round((float) rand(20, 50) / 10, 3);
                $outQty = round((float) rand(10, 30) / 10, 3);
                $balance = (float) $ingredient->current_stock + $inQty - $outQty;

                InventoryTransaction::query()->create([
                    'ingredient_id' => $ingredient->id,
                    'restaurant_id' => $restaurant->id,
                    'type' => 'in',
                    'quantity' => $inQty,
                    'balance_after' => (float) $ingredient->current_stock + $inQty,
                    'reference_type' => 'purchase_order',
                    'reference_id' => rand(1000, 9999),
                    'note' => 'Weekly supply restock',
                    'transacted_at' => Carbon::now()->subDays(3),
                ]);

                InventoryTransaction::query()->create([
                    'ingredient_id' => $ingredient->id,
                    'restaurant_id' => $restaurant->id,
                    'type' => 'out',
                    'quantity' => $outQty,
                    'balance_after' => $balance,
                    'reference_type' => 'kitchen_usage',
                    'reference_id' => rand(1000, 9999),
                    'note' => 'Daily production usage',
                    'transacted_at' => Carbon::now()->subDay(),
                ]);

                $ingredient->update(['current_stock' => $balance]);
            }

            $tables = collect(range(1, 6))->map(fn (int $index) => DiningTable::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => 'T-' . $index,
                'capacity' => Arr::random([2, 2, 4, 4, 6]),
                'location' => Arr::random(['Patio', 'Main Hall', 'Window Side']),
                'status' => Arr::random(['available', 'occupied']),
                'is_active' => true,
            ]));

            $shifts = collect([
                [
                    'name' => 'Morning Shift',
                    'starts_at' => Carbon::today()->setTime(6, 0),
                    'ends_at' => Carbon::today()->setTime(14, 0),
                ],
                [
                    'name' => 'Evening Shift',
                    'starts_at' => Carbon::today()->setTime(14, 0),
                    'ends_at' => Carbon::today()->setTime(22, 0),
                ],
            ])->map(fn (array $shift) => Shift::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $shift['name'],
                'starts_at' => $shift['starts_at'],
                'ends_at' => $shift['ends_at'],
                'status' => 'scheduled',
                'notes' => 'Auto seeded',
            ]));

            foreach ($shifts as $shift) {
                $restaurantStaff = $staffUsers->where('restaurant_id', $restaurant->id);
                foreach ($restaurantStaff->random(min(3, $restaurantStaff->count())) as $user) {
                    StaffShiftAssignment::query()->create([
                        'shift_id' => $shift->id,
                        'user_id' => $user->id,
                        'role' => Arr::random(['cashier', 'barista', 'runner']),
                        'status' => 'assigned',
                        'clock_in_at' => null,
                        'clock_out_at' => null,
                    ]);
                }
            }

            foreach (range(1, 5) as $orderOffset) {
                $table = $tables->random();
                $owner = $customerUsers->random();
                $orderedAt = Carbon::now()->subHours(rand(1, 72));

                $order = Order::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'table_id' => $table->id,
                    'user_id' => $owner->id,
                    'order_number' => Str::upper($restaurant->slug) . '-' . now()->format('Ymd') . '-' . $orderOffset . rand(10, 99),
                    'status' => Arr::random(['completed', 'preparing', 'served']),
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'notes' => 'Seed order',
                    'placed_at' => $orderedAt,
                    'closed_at' => $orderedAt->copy()->addMinutes(rand(20, 50)),
                ]);

                $lineItems = $menuItems->random(2);
                $subtotal = 0.0;

                foreach ($lineItems as $menuItem) {
                    $quantity = (float) Arr::random([1, 1, 2]);
                    $unitPrice = (float) $menuItem->price;
                    $lineTotal = round($quantity * $unitPrice, 2);
                    $subtotal += $lineTotal;

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItem->id,
                        'item_name' => $menuItem->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'notes' => null,
                    ]);
                }

                $tax = round($subtotal * 0.08, 2);
                $total = round($subtotal + $tax, 2);

                $order->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'changed_by' => $owner->id,
                    'changed_at' => $orderedAt,
                    'note' => 'Order placed',
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'changed_by' => $staffUsers->where('restaurant_id', $restaurant->id)->random()->id,
                    'changed_at' => $orderedAt->copy()->addMinutes(10),
                    'note' => 'Order progressed',
                ]);

                TableSession::query()->create([
                    'table_id' => $table->id,
                    'order_id' => $order->id,
                    'opened_at' => $orderedAt,
                    'closed_at' => $orderedAt->copy()->addMinutes(rand(35, 65)),
                    'guest_count' => rand(1, 5),
                    'status' => 'closed',
                ]);

                PaymentEvent::query()->create([
                    'order_id' => $order->id,
                    'amount' => $total,
                    'method' => Arr::random(['card', 'cash', 'wallet']),
                    'provider' => 'seed-gateway',
                    'provider_reference' => 'PAY-' . Str::upper(Str::random(8)),
                    'status' => 'completed',
                    'paid_at' => $orderedAt->copy()->addMinutes(rand(15, 45)),
                    'payload' => ['source' => 'seeder'],
                ]);
            }
        }
    }
}
