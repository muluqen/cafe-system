<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\OrderStatusHistoryController;
use App\Http\Controllers\Api\PaymentEventController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RbacController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RestaurantSettingsController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StaffShiftAssignmentController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TableSessionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\RecipeIngredientController;
use App\Http\Controllers\RolePermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
*/

    Route::prefix('v1')->group(function (): void {

    // ── Public (no auth) ─────────────────────────────────────
    Route::get('restaurants', [RestaurantController::class, 'listPublic']);
    Route::get('public/restaurants/{id}', [PublicController::class, 'restaurant']);
    Route::get('public/restaurants/{id}/menu_items', [PublicController::class, 'menuItems']);
    Route::get('public/restaurants/{id}/menu_categories', [PublicController::class, 'menuCategories']);
    Route::get('public/restaurants/{id}/ingredients', [PublicController::class, 'ingredients']);
    Route::get('public/restaurants/{id}/settings', [RestaurantSettingsController::class, 'publicShow']);

    // ── Auth ──────────────────────────────────────────────────
    Route::get('auth/restaurants', [AuthController::class, 'restaurants']);
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/register-restaurant', [AuthController::class, 'registerRestaurant']);
    Route::post('auth/login', [AuthController::class, 'login']);

    // ── Authenticated ─────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {

        // Profile
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Preferences
        Route::get('preferences', [PreferenceController::class, 'index']);
        Route::post('preferences', [PreferenceController::class, 'store']);

        // Feedback
        Route::get('feedback', [FeedbackController::class, 'index']);
        Route::post('feedback', [FeedbackController::class, 'store']);
        Route::put('feedback/{feedback}', [FeedbackController::class, 'update']);

        // Role Permissions
        Route::get('role_permissions', [RolePermissionController::class, 'index']);
        Route::post('role_permissions', [RolePermissionController::class, 'store']);

        // Checkout
        Route::post('checkout/process', [CheckoutController::class, 'process']);

        // Restaurant Settings
        Route::get('restaurant-settings', [RestaurantSettingsController::class, 'index']);
        Route::put('restaurant-settings', [RestaurantSettingsController::class, 'upsert']);
        Route::post('restaurant-settings/logo', [RestaurantSettingsController::class, 'uploadLogo']);

        // Recipe Ingredients (standalone resource)
        Route::get('recipe_ingredients', [RecipeIngredientController::class, 'index']);
        Route::post('recipe_ingredients', [RecipeIngredientController::class, 'store']);
        Route::put('recipe_ingredients/{recipeIngredient}', [RecipeIngredientController::class, 'update']);
        Route::delete('recipe_ingredients/{recipeIngredient}', [RecipeIngredientController::class, 'destroy']);

        // API Resources
        Route::apiResources([
            'users'                    => UserController::class,
            'menu_categories'          => MenuCategoryController::class,
            'menu_items'               => MenuItemController::class,
            'orders'                   => OrderController::class,
            'order_items'              => OrderItemController::class,
            'order_status_history'     => OrderStatusHistoryController::class,
            'ingredients'              => IngredientController::class,
            'inventory_transactions'   => InventoryTransactionController::class,
            'tables'                   => TableController::class,
            'table_sessions'           => TableSessionController::class,
            'shifts'                   => ShiftController::class,
            'staff_shift_assignments'  => StaffShiftAssignmentController::class,
            'payment_events'           => PaymentEventController::class,
        ]);

        // Restaurant CRUD (separate from public listPublic route)
        Route::post('restaurants', [RestaurantController::class, 'store']);
        Route::put('restaurants/{restaurant}', [RestaurantController::class, 'update']);
        Route::delete('restaurants/{restaurant}', [RestaurantController::class, 'destroy']);
        Route::get('restaurants/{restaurant}', [RestaurantController::class, 'show'])->name('restaurants.show');

        // ── Super Admin ────────────────────────────────────
        Route::middleware(['super.admin'])->prefix('admin')->group(function (): void {
            Route::get('/stats', [SuperAdminController::class, 'stats']);
            Route::get('/restaurants', [SuperAdminController::class, 'allRestaurants']);
            Route::get('/restaurants/pending', [SuperAdminController::class, 'pendingRestaurants']);
            Route::post('/restaurants/{id}/approve', [SuperAdminController::class, 'approveRestaurant']);
            Route::post('/restaurants/{id}/reject', [SuperAdminController::class, 'rejectRestaurant']);
            Route::post('/restaurants/{id}/suspend', [SuperAdminController::class, 'suspendRestaurant']);
            Route::post('/restaurants/{id}/reactivate', [SuperAdminController::class, 'reactivateRestaurant']);
            Route::get('/users', [SuperAdminController::class, 'allUsers']);
            Route::get('/notifications', [SuperAdminController::class, 'notifications']);
            Route::post('/notifications/{id}/read', [SuperAdminController::class, 'markNotificationRead']);
            Route::get('/analytics', [SuperAdminController::class, 'platformAnalytics']);
        });

        // ── RBAC ──────────────────────────────────────────
        Route::prefix('rbac')->group(function (): void {
            Route::get('/defaults/{role}', [RbacController::class, 'getDefaults']);
            Route::put('/defaults/{role}', [RbacController::class, 'updateBuiltinRolePermissions']);
            Route::get('/my-permissions', [RbacController::class, 'myPermissions']);
            Route::get('/custom-roles', [RbacController::class, 'getCustomRoles']);
            Route::post('/custom-roles', [RbacController::class, 'createCustomRole']);
            Route::put('/custom-roles/{id}', [RbacController::class, 'updateCustomRole']);
            Route::put('/custom-roles/{id}/permissions', [RbacController::class, 'updateCustomRolePermissions']);
            Route::delete('/custom-roles/{id}', [RbacController::class, 'deleteCustomRole']);
            Route::get('/users/{userId}/permissions', [RbacController::class, 'getUserPermissions']);
            Route::put('/users/{userId}/permissions', [RbacController::class, 'setUserPermissions']);
            Route::delete('/users/{userId}/permissions/{entityKey}', [RbacController::class, 'removeUserPermission']);
        });
    });
});
