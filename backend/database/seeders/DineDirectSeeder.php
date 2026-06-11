<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use App\Models\DiningTable;
use App\Models\Shift;
use App\Models\StaffShiftAssignment;
use App\Models\InventoryTransaction;
use App\Models\RestaurantSetting;
use App\Models\DefaultRolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DineDirectSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = $this->createRestaurant();
        $staff = $this->createStaff($restaurant);
        $categories = $this->createCategories($restaurant);
        $ingredients = $this->createIngredients($restaurant);
        $menuItems = $this->createMenuItems($restaurant, $categories);
        $this->createRecipeIngredients($restaurant, $menuItems, $ingredients);
        $this->createDiningTables($restaurant);
        $this->createShifts($restaurant, $staff);
        $this->createInventoryTransactions($restaurant, $ingredients);
        $this->createSettings($restaurant, $menuItems);
        $this->createRolePermissions($restaurant, $staff);
    }

    private function createRestaurant(): Restaurant
    {
        return Restaurant::create([
            'name' => 'DineDirect',
            'slug' => 'dinedirect',
            'cuisine_type' => 'Fast Food & Fresh Juices',
            'phone' => '+1-555-0199',
            'email' => 'info@dinedirect.com',
            'address' => '742 Evergreen Avenue, Downtown',
            'location' => 'Downtown, Springfield',
            'rating' => 4.6,
            'opening_time' => '07:00',
            'closing_time' => '23:00',
            'is_active' => true,
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    private function createStaff(Restaurant $restaurant): array
    {
        $staffData = [
            ['name' => 'Marcus Johnson',    'email' => 'marcus@dinedirect.com',    'staff_role' => 'manager'],
            ['name' => 'Sarah Williams',     'email' => 'sarah@dinedirect.com',     'staff_role' => 'floor_manager'],
            ['name' => 'David Chen',         'email' => 'david@dinedirect.com',     'staff_role' => 'cashier'],
            ['name' => 'Emily Rodriguez',    'email' => 'emily@dinedirect.com',     'staff_role' => 'cashier'],
            ['name' => 'James Thompson',     'email' => 'james@dinedirect.com',     'staff_role' => 'kitchen'],
            ['name' => 'Aisha Patel',        'email' => 'aisha@dinedirect.com',     'staff_role' => 'kitchen'],
            ['name' => 'Carlos Martinez',    'email' => 'carlos@dinedirect.com',    'staff_role' => 'kitchen'],
            ['name' => 'Olivia Brown',       'email' => 'olivia@dinedirect.com',    'staff_role' => 'server'],
            ['name' => 'Liam Davis',         'email' => 'liam@dinedirect.com',      'staff_role' => 'server'],
            ['name' => 'Sophia Wilson',      'email' => 'sophia@dinedirect.com',    'staff_role' => 'server'],
            ['name' => 'Noah Anderson',      'email' => 'noah@dinedirect.com',      'staff_role' => 'inventory'],
        ];

        $staff = [];
        foreach ($staffData as $s) {
            $staff[] = User::create([
                'name' => $s['name'],
                'email' => $s['email'],
                'password' => Hash::make('password123'),
                'role' => 'restaurant',
                'staff_role' => $s['staff_role'],
                'restaurant_id' => $restaurant->id,
            ]);
        }
        return $staff;
    }

    private function createCategories(Restaurant $restaurant): array
    {
        $cats = [
            ['name' => 'Burgers',          'display_order' => 1],
            ['name' => 'Pizza',            'display_order' => 2],
            ['name' => 'Sides',            'display_order' => 3],
            ['name' => 'Wraps & Burritos', 'display_order' => 4],
            ['name' => 'Fresh Juices',     'display_order' => 5],
            ['name' => 'Smoothies',        'display_order' => 6],
            ['name' => 'Specials',         'display_order' => 7],
        ];

        $categories = [];
        foreach ($cats as $cat) {
            $categories[$cat['name']] = MenuCategory::create([
                'restaurant_id' => $restaurant->id,
                'name' => $cat['name'],
                'display_order' => $cat['display_order'],
                'is_active' => true,
            ]);
        }
        return $categories;
    }

    private function createIngredients(Restaurant $restaurant): array
    {
        // Real USDA-based nutritional data and market pricing
        $data = [
            // Proteins
            ['name' => 'Beef Patty (80/20)',       'unit' => 'g',   'cost' => 12.00, 'cal' => 254, 'stock' => 50,  'reorder' => 10],
            ['name' => 'Chicken Breast',            'unit' => 'g',   'cost' => 9.50,  'cal' => 165, 'stock' => 60,  'reorder' => 12],
            ['name' => 'Chicken Thigh',             'unit' => 'g',   'cost' => 7.80,  'cal' => 209, 'stock' => 40,  'reorder' => 10],
            ['name' => 'Shawarma Meat (Chicken)',   'unit' => 'g',   'cost' => 10.00, 'cal' => 190, 'stock' => 35,  'reorder' => 8],
            ['name' => 'Lamb Mince',                'unit' => 'g',   'cost' => 16.00, 'cal' => 294, 'stock' => 20,  'reorder' => 5],
            ['name' => 'Bacon',                     'unit' => 'g',   'cost' => 14.00, 'cal' => 541, 'stock' => 15,  'reorder' => 4],

            // Dairy & Cheese
            ['name' => 'Cheddar Cheese',            'unit' => 'g',   'cost' => 8.00,  'cal' => 402, 'stock' => 25,  'reorder' => 5],
            ['name' => 'Mozzarella Cheese',         'unit' => 'g',   'cost' => 7.50,  'cal' => 280, 'stock' => 30,  'reorder' => 6],
            ['name' => 'Feta Cheese',               'unit' => 'g',   'cost' => 9.00,  'cal' => 264, 'stock' => 10,  'reorder' => 3],
            ['name' => 'Sour Cream',                'unit' => 'g',   'cost' => 4.50,  'cal' => 198, 'stock' => 15,  'reorder' => 4],
            ['name' => 'Greek Yogurt',              'unit' => 'g',   'cost' => 5.00,  'cal' => 59,  'stock' => 15,  'reorder' => 4],
            ['name' => 'Butter',                    'unit' => 'g',   'cost' => 6.00,  'cal' => 717, 'stock' => 10,  'reorder' => 3],

            // Bread & Wraps
            ['name' => 'Burger Bun',                'unit' => 'pcs', 'cost' => 0.60,  'cal' => 250, 'stock' => 80,  'reorder' => 20],
            ['name' => 'Pizza Dough',               'unit' => 'g',   'cost' => 1.50,  'cal' => 266, 'stock' => 40,  'reorder' => 10],
            ['name' => 'Pita Bread',                'unit' => 'pcs', 'cost' => 0.50,  'cal' => 275, 'stock' => 60,  'reorder' => 15],
            ['name' => 'Tortilla Wrap (Large)',     'unit' => 'pcs', 'cost' => 0.70,  'cal' => 312, 'stock' => 50,  'reorder' => 12],
            ['name' => 'French Fries (Frozen)',     'unit' => 'g',   'cost' => 3.50,  'cal' => 312, 'stock' => 40,  'reorder' => 10],

            // Vegetables
            ['name' => 'Lettuce',                   'unit' => 'g',   'cost' => 2.00,  'cal' => 15,  'stock' => 20,  'reorder' => 5],
            ['name' => 'Tomato',                    'unit' => 'g',   'cost' => 2.50,  'cal' => 18,  'stock' => 25,  'reorder' => 6],
            ['name' => 'Red Onion',                 'unit' => 'g',   'cost' => 1.80,  'cal' => 40,  'stock' => 15,  'reorder' => 4],
            ['name' => 'White Onion',               'unit' => 'g',   'cost' => 1.50,  'cal' => 40,  'stock' => 15,  'reorder' => 4],
            ['name' => 'Cucumber',                  'unit' => 'g',   'cost' => 1.50,  'cal' => 16,  'stock' => 15,  'reorder' => 4],
            ['name' => 'Bell Pepper',               'unit' => 'g',   'cost' => 3.00,  'cal' => 31,  'stock' => 12,  'reorder' => 3],
            ['name' => 'Jalapeño',                  'unit' => 'g',   'cost' => 4.00,  'cal' => 29,  'stock' => 8,   'reorder' => 2],
            ['name' => 'Garlic',                    'unit' => 'g',   'cost' => 5.00,  'cal' => 149, 'stock' => 5,   'reorder' => 1],
            ['name' => 'Avocado',                   'unit' => 'g',   'cost' => 8.00,  'cal' => 160, 'stock' => 12,  'reorder' => 3],
            ['name' => 'Pickles',                   'unit' => 'g',   'cost' => 3.00,  'cal' => 11,  'stock' => 10,  'reorder' => 3],
            ['name' => 'Corn Kernels',              'unit' => 'g',   'cost' => 2.50,  'cal' => 86,  'stock' => 10,  'reorder' => 3],
            ['name' => 'Cilantro',                  'unit' => 'g',   'cost' => 6.00,  'cal' => 23,  'stock' => 3,   'reorder' => 1],

            // Sauces & Condiments
            ['name' => 'Tomato Sauce',              'unit' => 'ml',  'cost' => 2.00,  'cal' => 74,  'stock' => 20,  'reorder' => 5],
            ['name' => 'Ketchup',                   'unit' => 'ml',  'cost' => 1.80,  'cal' => 112, 'stock' => 15,  'reorder' => 4],
            ['name' => 'Mustard',                   'unit' => 'ml',  'cost' => 2.00,  'cal' => 60,  'stock' => 10,  'reorder' => 3],
            ['name' => 'Mayonnaise',                'unit' => 'ml',  'cost' => 3.00,  'cal' => 680, 'stock' => 12,  'reorder' => 3],
            ['name' => 'BBQ Sauce',                 'unit' => 'ml',  'cost' => 3.50,  'cal' => 172, 'stock' => 10,  'reorder' => 3],
            ['name' => 'Hot Sauce',                 'unit' => 'ml',  'cost' => 4.00,  'cal' => 11,  'stock' => 8,   'reorder' => 2],
            ['name' => 'Tahini',                    'unit' => 'ml',  'cost' => 8.00,  'cal' => 595, 'stock' => 8,   'reorder' => 2],
            ['name' => 'Hummus',                    'unit' => 'g',   'cost' => 6.00,  'cal' => 166, 'stock' => 10,  'reorder' => 3],
            ['name' => 'Ranch Dressing',            'unit' => 'ml',  'cost' => 3.50,  'cal' => 448, 'stock' => 8,   'reorder' => 2],
            ['name' => 'Buffalo Sauce',             'unit' => 'ml',  'cost' => 4.50,  'cal' => 70,  'stock' => 6,   'reorder' => 2],

            // Rice & Beans
            ['name' => 'Basmati Rice',              'unit' => 'g',   'cost' => 2.50,  'cal' => 130, 'stock' => 50,  'reorder' => 12],
            ['name' => 'Black Beans',               'unit' => 'g',   'cost' => 2.00,  'cal' => 132, 'stock' => 20,  'reorder' => 5],
            ['name' => 'Refried Beans',             'unit' => 'g',   'cost' => 2.50,  'cal' => 218, 'stock' => 15,  'reorder' => 4],

            // Spices & Seasonings
            ['name' => 'Salt',                      'unit' => 'g',   'cost' => 0.50,  'cal' => 0,   'stock' => 10,  'reorder' => 3],
            ['name' => 'Black Pepper',              'unit' => 'g',   'cost' => 12.00, 'cal' => 251, 'stock' => 2,   'reorder' => 0.5],
            ['name' => 'Cumin',                     'unit' => 'g',   'cost' => 10.00, 'cal' => 375, 'stock' => 2,   'reorder' => 0.5],
            ['name' => 'Paprika',                   'unit' => 'g',   'cost' => 9.00,  'cal' => 282, 'stock' => 2,   'reorder' => 0.5],
            ['name' => 'Oregano',                   'unit' => 'g',   'cost' => 11.00, 'cal' => 265, 'stock' => 1.5, 'reorder' => 0.5],
            ['name' => 'Garlic Powder',             'unit' => 'g',   'cost' => 8.00,  'cal' => 331, 'stock' => 2,   'reorder' => 0.5],
            ['name' => 'Onion Powder',              'unit' => 'g',   'cost' => 7.00,  'cal' => 341, 'stock' => 2,   'reorder' => 0.5],
            ['name' => 'Chili Flakes',              'unit' => 'g',   'cost' => 8.50,  'cal' => 313, 'stock' => 1.5, 'reorder' => 0.5],
            ['name' => 'Italian Seasoning',         'unit' => 'g',   'cost' => 7.50,  'cal' => 250, 'stock' => 2,   'reorder' => 0.5],

            // Fruits (for juices & smoothies)
            ['name' => 'Orange',                    'unit' => 'g',   'cost' => 2.00,  'cal' => 47,  'stock' => 30,  'reorder' => 8],
            ['name' => 'Mango',                     'unit' => 'g',   'cost' => 3.50,  'cal' => 60,  'stock' => 15,  'reorder' => 4],
            ['name' => 'Strawberry',                'unit' => 'g',   'cost' => 6.00,  'cal' => 32,  'stock' => 12,  'reorder' => 3],
            ['name' => 'Banana',                    'unit' => 'g',   'cost' => 1.50,  'cal' => 89,  'stock' => 20,  'reorder' => 5],
            ['name' => 'Watermelon',                'unit' => 'g',   'cost' => 1.20,  'cal' => 30,  'stock' => 20,  'reorder' => 5],
            ['name' => 'Pineapple',                 'unit' => 'g',   'cost' => 2.50,  'cal' => 50,  'stock' => 10,  'reorder' => 3],
            ['name' => 'Apple',                     'unit' => 'g',   'cost' => 2.00,  'cal' => 52,  'stock' => 15,  'reorder' => 4],
            ['name' => 'Lemon',                     'unit' => 'g',   'cost' => 3.00,  'cal' => 29,  'stock' => 10,  'reorder' => 3],
            ['name' => 'Lime',                      'unit' => 'g',   'cost' => 2.50,  'cal' => 30,  'stock' => 10,  'reorder' => 3],
            ['name' => 'Ginger',                    'unit' => 'g',   'cost' => 6.00,  'cal' => 80,  'stock' => 5,   'reorder' => 1],
            ['name' => 'Grapes',                    'unit' => 'g',   'cost' => 4.00,  'cal' => 69,  'stock' => 8,   'reorder' => 2],

            // Smoothie add-ons
            ['name' => 'Honey',                     'unit' => 'ml',  'cost' => 10.00, 'cal' => 304, 'stock' => 8,   'reorder' => 2],
            ['name' => 'Oats',                      'unit' => 'g',   'cost' => 3.00,  'cal' => 389, 'stock' => 10,  'reorder' => 3],
            ['name' => 'Protein Powder',            'unit' => 'g',   'cost' => 20.00, 'cal' => 370, 'stock' => 5,   'reorder' => 1],
            ['name' => 'Ice Cream (Vanilla)',       'unit' => 'g',   'cost' => 5.00,  'cal' => 207, 'stock' => 10,  'reorder' => 3],
            ['name' => 'Whipped Cream',             'unit' => 'ml',  'cost' => 4.00,  'cal' => 257, 'stock' => 8,   'reorder' => 2],
            ['name' => 'Chocolate Syrup',           'unit' => 'ml',  'cost' => 5.00,  'cal' => 286, 'stock' => 6,   'reorder' => 2],
            ['name' => 'Caramel Sauce',             'unit' => 'ml',  'cost' => 5.50,  'cal' => 250, 'stock' => 6,   'reorder' => 2],

            // Misc
            ['name' => 'Olive Oil',                 'unit' => 'ml',  'cost' => 8.00,  'cal' => 884, 'stock' => 10,  'reorder' => 3],
            ['name' => 'Vinegar',                   'unit' => 'ml',  'cost' => 1.50,  'cal' => 18,  'stock' => 5,   'reorder' => 2],
            ['name' => 'Sugar',                     'unit' => 'g',   'cost' => 1.00,  'cal' => 387, 'stock' => 15,  'reorder' => 4],
            ['name' => 'Chicken Broth',             'unit' => 'ml',  'cost' => 1.50,  'cal' => 7,   'stock' => 10,  'reorder' => 3],
            ['name' => 'Coconut Milk',              'unit' => 'ml',  'cost' => 3.00,  'cal' => 230, 'stock' => 8,   'reorder' => 2],
            ['name' => 'Condensed Milk',            'unit' => 'ml',  'cost' => 3.50,  'cal' => 321, 'stock' => 6,   'reorder' => 2],
        ];

        $ingredients = [];
        foreach ($data as $d) {
            $ingredients[$d['name']] = Ingredient::create([
                'restaurant_id' => $restaurant->id,
                'name' => $d['name'],
                'unit' => $d['unit'],
                'cost_per_unit' => $d['cost'],
                'calories_per_unit' => $d['cal'],
                'current_stock' => $d['stock'],
                'reorder_level' => $d['reorder'],
                'is_active' => true,
            ]);
        }
        return $ingredients;
    }

    private function createMenuItems(Restaurant $restaurant, array $cats): array
    {
        $items = [];

        // ── BURGERS ──
        $items['Classic Burger'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Burgers']->id,
            'name' => 'Classic Burger',
            'sku' => 'DD-BURG-001',
            'description' => 'Juicy beef patty with lettuce, tomato, onion, and our signature sauce on a toasted bun.',
            'price' => 8.99,
            'preparation_time_minutes' => 12,
        ]);
        $items['Bacon Cheeseburger'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Burgers']->id,
            'name' => 'Bacon Cheeseburger',
            'sku' => 'DD-BURG-002',
            'description' => 'Beef patty with crispy bacon, melted cheddar, lettuce, tomato, and smoky BBQ sauce.',
            'price' => 10.99,
            'preparation_time_minutes' => 14,
        ]);
        $items['Chicken Burger'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Burgers']->id,
            'name' => 'Grilled Chicken Burger',
            'sku' => 'DD-BURG-003',
            'description' => 'Grilled chicken breast with lettuce, tomato, and mayo on a toasted bun.',
            'price' => 9.49,
            'preparation_time_minutes' => 12,
        ]);
        $items['Spicy Jalapeño Burger'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Burgers']->id,
            'name' => 'Spicy Jalapeño Burger',
            'sku' => 'DD-BURG-004',
            'description' => 'Beef patty with jalapeños, pepper jack cheese, lettuce, and hot sauce.',
            'price' => 9.99,
            'preparation_time_minutes' => 12,
        ]);

        // ── PIZZA ──
        $items['Margherita Pizza'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Pizza']->id,
            'name' => 'Margherita Pizza',
            'sku' => 'DD-PIZZ-001',
            'description' => 'Classic tomato sauce, fresh mozzarella, and basil on hand-tossed dough.',
            'price' => 12.99,
            'preparation_time_minutes' => 18,
        ]);
        $items['Pepperoni Pizza'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Pizza']->id,
            'name' => 'Pepperoni Pizza',
            'sku' => 'DD-PIZZ-002',
            'description' => 'Tomato sauce, mozzarella, and premium pepperoni slices.',
            'price' => 13.99,
            'preparation_time_minutes' => 18,
        ]);
        $items['BBQ Chicken Pizza'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Pizza']->id,
            'name' => 'BBQ Chicken Pizza',
            'sku' => 'DD-PIZZ-003',
            'description' => 'BBQ sauce base, grilled chicken, red onion, cilantro, and mozzarella.',
            'price' => 14.99,
            'preparation_time_minutes' => 20,
        ]);

        // ── SIDES ──
        $items['French Fries'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Sides']->id,
            'name' => 'French Fries',
            'sku' => 'DD-SIDE-001',
            'description' => 'Golden crispy fries seasoned with sea salt.',
            'price' => 3.99,
            'preparation_time_minutes' => 8,
        ]);
        $items['Loaded Fries'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Sides']->id,
            'name' => 'Loaded Fries',
            'sku' => 'DD-SIDE-002',
            'description' => 'Fries topped with melted cheddar, bacon bits, jalapeños, and ranch.',
            'price' => 6.99,
            'preparation_time_minutes' => 12,
        ]);
        $items['Onion Rings'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Sides']->id,
            'name' => 'Onion Rings',
            'sku' => 'DD-SIDE-003',
            'description' => 'Beer-battered onion rings, golden fried.',
            'price' => 4.99,
            'preparation_time_minutes' => 8,
        ]);
        $items['Coleslaw'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Sides']->id,
            'name' => 'Coleslaw',
            'sku' => 'DD-SIDE-004',
            'description' => 'Fresh cabbage and carrot in a creamy dressing.',
            'price' => 2.99,
            'preparation_time_minutes' => 5,
        ]);

        // ── WRAPS & BURRITOS ──
        $items['Chicken Shawarma'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Wraps & Burritos']->id,
            'name' => 'Chicken Shawarma Wrap',
            'sku' => 'DD-WRAP-001',
            'description' => 'Seasoned chicken with garlic sauce, pickles, and fresh veggies in pita.',
            'price' => 9.99,
            'preparation_time_minutes' => 10,
        ]);
        $items['Chicken Burrito'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Wraps & Burritos']->id,
            'name' => 'Chicken Burrito',
            'sku' => 'DD-WRAP-002',
            'description' => 'Grilled chicken, rice, black beans, cheese, sour cream, and salsa in a tortilla.',
            'price' => 10.99,
            'preparation_time_minutes' => 12,
        ]);
        $items['Veggie Wrap'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Wraps & Burritos']->id,
            'name' => 'Veggie Wrap',
            'sku' => 'DD-WRAP-003',
            'description' => 'Grilled vegetables, hummus, feta, and mixed greens in a tortilla.',
            'price' => 8.99,
            'preparation_time_minutes' => 8,
        ]);

        // ── FRESH JUICES ──
        $items['Fresh Orange Juice'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Fresh Juices']->id,
            'name' => 'Fresh Orange Juice',
            'sku' => 'DD-JUIC-001',
            'description' => 'Freshly squeezed orange juice, no added sugar.',
            'price' => 4.99,
            'preparation_time_minutes' => 5,
        ]);
        $items['Mango Juice'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Fresh Juices']->id,
            'name' => 'Mango Juice',
            'sku' => 'DD-JUIC-002',
            'description' => 'Fresh mango blended to smooth perfection.',
            'price' => 5.49,
            'preparation_time_minutes' => 5,
        ]);
        $items['Watermelon Juice'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Fresh Juices']->id,
            'name' => 'Watermelon Juice',
            'sku' => 'DD-JUIC-003',
            'description' => 'Refreshing watermelon juice with a hint of lime.',
            'price' => 4.49,
            'preparation_time_minutes' => 5,
        ]);
        $items['Pineapple Juice'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Fresh Juices']->id,
            'name' => 'Pineapple Juice',
            'sku' => 'DD-JUIC-004',
            'description' => 'Fresh pressed pineapple juice.',
            'price' => 4.99,
            'preparation_time_minutes' => 5,
        ]);
        $items['Mixed Berry Juice'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Fresh Juices']->id,
            'name' => 'Mixed Berry Juice',
            'sku' => 'DD-JUIC-005',
            'description' => 'Strawberry, grape, and apple juice blend.',
            'price' => 5.49,
            'preparation_time_minutes' => 5,
        ]);

        // ── SMOOTHIES ──
        $items['Mango Tango Smoothie'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Smoothies']->id,
            'name' => 'Mango Tango Smoothie',
            'sku' => 'DD-SMOO-001',
            'description' => 'Mango, Greek yogurt, honey, and ice blended smooth.',
            'price' => 6.49,
            'preparation_time_minutes' => 5,
        ]);
        $items['Strawberry Banana Smoothie'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Smoothies']->id,
            'name' => 'Strawberry Banana Smoothie',
            'sku' => 'DD-SMOO-002',
            'description' => 'Fresh strawberries, banana, and yogurt blended smooth.',
            'price' => 5.99,
            'preparation_time_minutes' => 5,
        ]);
        $items['Green Power Smoothie'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Smoothies']->id,
            'name' => 'Green Power Smoothie',
            'sku' => 'DD-SMOO-003',
            'description' => 'Banana, oats, honey, and peanut butter for an energy boost.',
            'price' => 6.99,
            'preparation_time_minutes' => 5,
        ]);

        // ── SPECIALS ──
        $items['DineDirect Special Burger'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Specials']->id,
            'name' => 'DineDirect Special Burger',
            'sku' => 'DD-SPCL-001',
            'description' => 'Double beef patty, caramelized onions, cheddar, bacon, avocado, and special sauce.',
            'price' => 14.99,
            'preparation_time_minutes' => 18,
        ]);
        $items['Shawarma Platter'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Specials']->id,
            'name' => 'Shawarma Platter',
            'sku' => 'DD-SPCL-002',
            'description' => 'Chicken shawarma with rice, hummus, salad, and garlic sauce.',
            'price' => 13.99,
            'preparation_time_minutes' => 15,
        ]);
        $items['BBQ Bacon Pizza'] = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $cats['Specials']->id,
            'name' => 'BBQ Bacon Pizza',
            'sku' => 'DD-SPCL-003',
            'description' => 'BBQ sauce, bacon, chicken, red onion, and mozzarella.',
            'price' => 15.99,
            'preparation_time_minutes' => 20,
        ]);

        return $items;
    }

    private function createRecipeIngredients(Restaurant $restaurant, array $items, array $ings): void
    {
        $recipes = [
            // Classic Burger
            'Classic Burger' => [
                ['Beef Patty (80/20)', 200],
                ['Burger Bun', 1],
                ['Lettuce', 30],
                ['Tomato', 40],
                ['White Onion', 20],
                ['Cheddar Cheese', 20],
                ['Ketchup', 15],
                ['Mustard', 10],
                ['Mayonnaise', 10],
                ['Pickles', 15],
            ],
            // Bacon Cheeseburger
            'Bacon Cheeseburger' => [
                ['Beef Patty (80/20)', 200],
                ['Burger Bun', 1],
                ['Bacon', 40],
                ['Cheddar Cheese', 30],
                ['Lettuce', 20],
                ['Tomato', 30],
                ['BBQ Sauce', 20],
            ],
            // Chicken Burger
            'Chicken Burger' => [
                ['Chicken Breast', 180],
                ['Burger Bun', 1],
                ['Lettuce', 25],
                ['Tomato', 30],
                ['Mayonnaise', 15],
            ],
            // Spicy Jalapeño Burger
            'Spicy Jalapeño Burger' => [
                ['Beef Patty (80/20)', 200],
                ['Burger Bun', 1],
                ['Jalapeño', 30],
                ['Cheddar Cheese', 25],
                ['Lettuce', 20],
                ['Hot Sauce', 15],
            ],
            // Margherita Pizza
            'Margherita Pizza' => [
                ['Pizza Dough', 250],
                ['Tomato Sauce', 80],
                ['Mozzarella Cheese', 120],
                ['Olive Oil', 10],
                ['Oregano', 2],
                ['Italian Seasoning', 1],
            ],
            // Pepperoni Pizza
            'Pepperoni Pizza' => [
                ['Pizza Dough', 250],
                ['Tomato Sauce', 80],
                ['Mozzarella Cheese', 120],
                ['Bacon', 60],
                ['Oregano', 2],
                ['Chili Flakes', 1],
            ],
            // BBQ Chicken Pizza
            'BBQ Chicken Pizza' => [
                ['Pizza Dough', 250],
                ['BBQ Sauce', 60],
                ['Chicken Breast', 120],
                ['Mozzarella Cheese', 100],
                ['Red Onion', 30],
                ['Cilantro', 5],
            ],
            // French Fries
            'French Fries' => [
                ['French Fries (Frozen)', 150],
                ['Salt', 2],
            ],
            // Loaded Fries
            'Loaded Fries' => [
                ['French Fries (Frozen)', 150],
                ['Cheddar Cheese', 40],
                ['Bacon', 20],
                ['Jalapeño', 15],
                ['Ranch Dressing', 20],
                ['Salt', 2],
            ],
            // Onion Rings
            'Onion Rings' => [
                ['White Onion', 120],
                ['Salt', 2],
            ],
            // Coleslaw
            'Coleslaw' => [
                ['Lettuce', 80],
                ['Carrot', 30],
                ['Mayonnaise', 20],
                ['Vinegar', 5],
                ['Sugar', 5],
            ],
            // Chicken Shawarma
            'Chicken Shawarma' => [
                ['Shawarma Meat (Chicken)', 180],
                ['Pita Bread', 1],
                ['Garlic', 5],
                ['Tahini', 15],
                ['Pickles', 20],
                ['Tomato', 30],
                ['White Onion', 20],
                ['Cucumber', 20],
            ],
            // Chicken Burrito
            'Chicken Burrito' => [
                ['Tortilla Wrap (Large)', 1],
                ['Chicken Breast', 150],
                ['Basmati Rice', 80],
                ['Black Beans', 60],
                ['Cheddar Cheese', 30],
                ['Sour Cream', 20],
                ['Lettuce', 20],
                ['Tomato', 25],
            ],
            // Veggie Wrap
            'Veggie Wrap' => [
                ['Tortilla Wrap (Large)', 1],
                ['Bell Pepper', 40],
                ['White Onion', 20],
                ['Corn Kernels', 30],
                ['Hummus', 30],
                ['Feta Cheese', 20],
                ['Lettuce', 20],
            ],
            // Fresh Orange Juice
            'Fresh Orange Juice' => [
                ['Orange', 300],
                ['Sugar', 5],
            ],
            // Mango Juice
            'Mango Juice' => [
                ['Mango', 250],
                ['Sugar', 8],
            ],
            // Watermelon Juice
            'Watermelon Juice' => [
                ['Watermelon', 350],
                ['Lime', 10],
            ],
            // Pineapple Juice
            'Pineapple Juice' => [
                ['Pineapple', 280],
                ['Sugar', 5],
            ],
            // Mixed Berry Juice
            'Mixed Berry Juice' => [
                ['Strawberry', 120],
                ['Grapes', 150],
                ['Apple', 100],
            ],
            // Mango Tango Smoothie
            'Mango Tango Smoothie' => [
                ['Mango', 200],
                ['Greek Yogurt', 100],
                ['Honey', 15],
                ['Ice Cream (Vanilla)', 50],
            ],
            // Strawberry Banana Smoothie
            'Strawberry Banana Smoothie' => [
                ['Strawberry', 150],
                ['Banana', 100],
                ['Greek Yogurt', 80],
                ['Honey', 10],
            ],
            // Green Power Smoothie
            'Green Power Smoothie' => [
                ['Banana', 120],
                ['Oats', 30],
                ['Honey', 15],
                ['Peanut Butter', 20],
                ['Greek Yogurt', 60],
            ],
            // DineDirect Special Burger
            'DineDirect Special Burger' => [
                ['Beef Patty (80/20)', 200],
                ['Beef Patty (80/20)', 200],
                ['Burger Bun', 1],
                ['Bacon', 40],
                ['Cheddar Cheese', 30],
                ['Avocado', 50],
                ['White Onion', 20],
                ['Special Sauce', 20],
            ],
            // Shawarma Platter
            'Shawarma Platter' => [
                ['Shawarma Meat (Chicken)', 200],
                ['Basmati Rice', 150],
                ['Hummus', 60],
                ['Lettuce', 40],
                ['Tomato', 40],
                ['Cucumber', 30],
                ['Tahini', 20],
                ['Garlic', 5],
            ],
            // BBQ Bacon Pizza
            'BBQ Bacon Pizza' => [
                ['Pizza Dough', 250],
                ['BBQ Sauce', 60],
                ['Chicken Breast', 100],
                ['Bacon', 50],
                ['Mozzarella Cheese', 120],
                ['Red Onion', 25],
                ['Cilantro', 5],
            ],
        ];

        foreach ($recipes as $itemName => $recipe) {
            if (!isset($items[$itemName])) continue;
            foreach ($recipe as [$ingName, $qty]) {
                if (!isset($ings[$ingName])) continue;
                RecipeIngredient::create([
                    'restaurant_id' => $restaurant->id,
                    'menu_item_id' => $items[$itemName]->id,
                    'ingredient_id' => $ings[$ingName]->id,
                    'quantity_required' => $qty,
                ]);
            }
        }
    }

    private function createDiningTables(Restaurant $restaurant): void
    {
        $tables = [
            ['name' => 'T1',  'capacity' => 2,  'location' => 'Indoor'],
            ['name' => 'T2',  'capacity' => 2,  'location' => 'Indoor'],
            ['name' => 'T3',  'capacity' => 4,  'location' => 'Indoor'],
            ['name' => 'T4',  'capacity' => 4,  'location' => 'Indoor'],
            ['name' => 'T5',  'capacity' => 6,  'location' => 'Indoor'],
            ['name' => 'T6',  'capacity' => 6,  'location' => 'Indoor'],
            ['name' => 'T7',  'capacity' => 8,  'location' => 'Indoor'],
            ['name' => 'T8',  'capacity' => 4,  'location' => 'Patio'],
            ['name' => 'T9',  'capacity' => 4,  'location' => 'Patio'],
            ['name' => 'T10', 'capacity' => 6,  'location' => 'Patio'],
            ['name' => 'BAR', 'capacity' => 1,  'location' => 'Bar Counter'],
        ];

        foreach ($tables as $t) {
            DiningTable::create([
                'restaurant_id' => $restaurant->id,
                'name' => $t['name'],
                'capacity' => $t['capacity'],
                'location' => $t['location'],
                'status' => 'available',
                'is_active' => true,
            ]);
        }
    }

    private function createShifts(Restaurant $restaurant, array $staff): void
    {
        $today = Carbon::today();

        $shifts = [
            ['name' => 'Morning Shift',  'start' => $today->copy()->setTime(7, 0),  'end' => $today->copy()->setTime(15, 0)],
            ['name' => 'Evening Shift',  'start' => $today->copy()->setTime(15, 0), 'end' => $today->copy()->setTime(23, 0)],
        ];

        foreach ($shifts as $s) {
            $shift = Shift::create([
                'restaurant_id' => $restaurant->id,
                'name' => $s['name'],
                'starts_at' => $s['start'],
                'ends_at' => $s['end'],
                'status' => 'scheduled',
            ]);

            // Assign some staff to each shift
            $roleMap = [
                'manager' => 'manager',
                'floor_manager' => 'floor_manager',
                'cashier' => 'cashier',
                'kitchen' => 'kitchen',
                'server' => 'server',
            ];

            foreach ($staff as $member) {
                $role = $member->staff_role;
                if (isset($roleMap[$role])) {
                    StaffShiftAssignment::create([
                        'shift_id' => $shift->id,
                        'user_id' => $member->id,
                        'role' => $role,
                        'status' => 'assigned',
                    ]);
                }
            }
        }
    }

    private function createInventoryTransactions(Restaurant $restaurant, array $ingredients): void
    {
        $now = now();
        foreach ($ingredients as $ing) {
            InventoryTransaction::create([
                'ingredient_id' => $ing->id,
                'restaurant_id' => $restaurant->id,
                'type' => 'in',
                'quantity' => $ing->current_stock,
                'balance_after' => $ing->current_stock,
                'reference_type' => 'initial_stock',
                'note' => 'Initial stock from seeder',
                'transacted_at' => $now,
            ]);
        }
    }

    private function createSettings(Restaurant $restaurant, array $menuItems): void
    {
        RestaurantSetting::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'DineDirect',
            'motto' => 'Fresh food, fast service, fair prices.',
            'banner_message' => 'Welcome to DineDirect! Try our Special of the Day.',
            'today_special_id' => $menuItems['DineDirect Special Burger']->id ?? null,
            'brand_colors' => ['primary' => '#FF6B35', 'secondary' => '#004E89', 'accent' => '#FFF8F0'],
            'phone' => '+1-555-0199',
            'address' => '742 Evergreen Avenue, Downtown, Springfield',
            'operating_hours' => [
                'monday' => ['open' => '07:00', 'close' => '23:00'],
                'tuesday' => ['open' => '07:00', 'close' => '23:00'],
                'wednesday' => ['open' => '07:00', 'close' => '23:00'],
                'thursday' => ['open' => '07:00', 'close' => '23:00'],
                'friday' => ['open' => '07:00', 'close' => '23:00'],
                'saturday' => ['open' => '08:00', 'close' => '23:00'],
                'sunday' => ['open' => '08:00', 'close' => '22:00'],
            ],
        ]);
    }

    private function createRolePermissions(Restaurant $restaurant, array $staff): void
    {
        $permissions = DefaultRolePermission::all();
        foreach ($staff as $member) {
            foreach ($permissions as $perm) {
                if ($perm->role_name === $member->staff_role) {
                    \App\Models\RolePermission::create([
                        'restaurant_id' => $restaurant->id,
                        'user_id' => $member->id,
                        'staff_role' => $member->staff_role,
                        'entity_key' => $perm->entity_key,
                        'can_read' => $perm->can_read,
                        'can_write' => $perm->can_write,
                    ]);
                }
            }
        }
    }
}
