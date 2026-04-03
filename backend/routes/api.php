<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\OrderStatusHistoryController;
use App\Http\Controllers\Api\PaymentEventController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StaffShiftAssignmentController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TableSessionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('auth/restaurants', [AuthController::class, 'restaurants']);
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/register-restaurant', [AuthController::class, 'registerRestaurant']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResources([
        'users' => UserController::class,
        'restaurants' => RestaurantController::class,
        'menu_categories' => MenuCategoryController::class,
        'menu_items' => MenuItemController::class,
        'orders' => OrderController::class,
        'order_items' => OrderItemController::class,
        'order_status_history' => OrderStatusHistoryController::class,
        'preferences' => PreferenceController::class,
        'ingredients' => IngredientController::class,
        'inventory_transactions' => InventoryTransactionController::class,
        'tables' => TableController::class,
        'table_sessions' => TableSessionController::class,
        'shifts' => ShiftController::class,
        'staff_shift_assignments' => StaffShiftAssignmentController::class,
        'payment_events' => PaymentEventController::class,
    ]);
});
