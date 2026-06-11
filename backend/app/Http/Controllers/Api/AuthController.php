<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Restaurant\CreateRestaurantRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles authentication endpoints.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * List active restaurants for selection.
     */
    public function restaurants(): JsonResponse
    {
        return ApiResponse::success($this->authService->listRestaurants());
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success($result, 'Registered', 201);
    }

    /**
     * Register a new restaurant with owner account.
     */
    public function registerRestaurant(CreateRestaurantRequest $request): JsonResponse
    {
        $result = $this->authService->registerRestaurant($request->validated());

        return ApiResponse::success($result, 'Restaurant registered', 201);
    }

    /**
     * Authenticate a user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success($result, 'Logged in');
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());
        $permissions = $user->effectivePermissions();

        return ApiResponse::success([
            'user' => array_merge($user->toArray(), [
                'is_super_admin' => (bool) $user->is_super_admin,
                'staff_role' => $user->staff_role,
                'restaurant_id' => $user->restaurant_id,
                'custom_role_id' => $user->custom_role_id,
            ]),
            'permissions' => $permissions,
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logged out');
    }
}
