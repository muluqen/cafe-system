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
                'name' => 'Addis Ababa Cafe',
                'slug' => 'addis-ababa-cafe',
                'phone' => '+1-555-1000',
                'email' => 'hello@addiscafe.local',
                'address' => '123 Main St',
                'is_active' => true,
            ],
            [
                'name' => 'Lalibela Restaurant',
                'slug' => 'lalibela-restaurant',
                'phone' => '+1-555-2000',
                'email' => 'contact@lalibelarestaurant.local',
                'address' => '42 River Ave',
                'is_active' => true,
            ],
        ])->map(fn(array $restaurant) => Restaurant::query()->create($restaurant));

        $staffUsers = collect([
            ['name' => 'Addis Manager', 'email' => 'manager@addiscafe.local', 'restaurant_idx' => 0, 'staff_role' => 'manager'],
            ['name' => 'Addis Cashier', 'email' => 'cashier@addiscafe.local', 'restaurant_idx' => 0, 'staff_role' => 'cashier'],
            ['name' => 'Addis Barista', 'email' => 'barista@addiscafe.local', 'restaurant_idx' => 0, 'staff_role' => 'barista'],
            ['name' => 'Lalibela Manager', 'email' => 'manager@lalibelarestaurant.local', 'restaurant_idx' => 1, 'staff_role' => 'manager'],
            ['name' => 'Lalibela Cashier', 'email' => 'cashier@lalibelarestaurant.local', 'restaurant_idx' => 1, 'staff_role' => 'cashier'],
            ['name' => 'Lalibela Barista', 'email' => 'barista@lalibelarestaurant.local', 'restaurant_idx' => 1, 'staff_role' => 'barista'],
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
        ])->map(fn(array $user) => User::query()->create([
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
                ['name' => 'Mains (Wots & Tibs)', 'display_order' => 1],
                ['name' => 'Vegetarian (Yetsom)', 'display_order' => 2],
                ['name' => 'Drinks', 'display_order' => 3],
            ])->map(fn(array $category) => MenuCategory::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $category['name'],
                'display_order' => $category['display_order'],
                'is_active' => true,
            ]));

            $menuItems = collect([
                ['category' => 'Mains (Wots & Tibs)', 'name' => 'Doro Wat', 'price' => 18.50, 'prep' => 25],
                ['category' => 'Mains (Wots & Tibs)', 'name' => 'Beef Tibs', 'price' => 19.00, 'prep' => 15],
                ['category' => 'Mains (Wots & Tibs)', 'name' => 'Kitfo', 'price' => 22.00, 'prep' => 12],
                ['category' => 'Vegetarian (Yetsom)', 'name' => 'Shiro Wat', 'price' => 14.50, 'prep' => 10],
                ['category' => 'Vegetarian (Yetsom)', 'name' => 'Misir Wat', 'price' => 15.00, 'prep' => 10],
                ['category' => 'Vegetarian (Yetsom)', 'name' => 'Gomen', 'price' => 13.50, 'prep' => 8],
                ['category' => 'Drinks', 'name' => 'Ethiopian Coffee', 'price' => 4.50, 'prep' => 10],
                ['category' => 'Drinks', 'name' => 'Tej (Honey Wine)', 'price' => 8.00, 'prep' => 2],
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
                ['name' => 'Teff Flour', 'unit' => 'kg', 'stock' => 50.000, 'reorder' => 15.000, 'cost' => 4.50],
                ['name' => 'Berbere Spice', 'unit' => 'kg', 'stock' => 10.000, 'reorder' => 3.000, 'cost' => 12.00],
                ['name' => 'Niter Kibbeh', 'unit' => 'kg', 'stock' => 8.000, 'reorder' => 2.000, 'cost' => 18.00],
                ['name' => 'Chicken', 'unit' => 'kg', 'stock' => 30.000, 'reorder' => 10.000, 'cost' => 5.50],
                ['name' => 'Beef', 'unit' => 'kg', 'stock' => 25.000, 'reorder' => 8.000, 'cost' => 8.00],
                ['name' => 'Coffee Beans (Yirgacheffe)', 'unit' => 'kg', 'stock' => 15.000, 'reorder' => 5.000, 'cost' => 15.00],
            ])->map(fn(array $ingredient) => Ingredient::query()->create([
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

            $tables = collect(range(1, 6))->map(fn(int $index) => DiningTable::query()->create([
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
            ])->map(fn(array $shift) => Shift::query()->create([
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
