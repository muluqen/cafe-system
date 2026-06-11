<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantSetting;
use App\Services\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RestaurantSettingsController extends Controller
{
    public function __construct(
        private readonly RbacService $rbacService
    ) {}

    private function resolveRestaurantId(Request $request): ?int
    {
        $user = $request->user();
        if ($user && $user->role === 'restaurant' && $user->restaurant_id) {
            return (int) $user->restaurant_id;
        }
        return $request->header('X-Restaurant-Id')
            ? (int) $request->header('X-Restaurant-Id')
            : null;
    }

    private function checkPermission(Request $request, string $entityKey, string $action = 'read'): bool
    {
        $user = $request->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        return $this->rbacService->canAccess($user, $entityKey, $action);
    }

    /**
     * GET /api/v1/restaurant-settings
     */
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $this->resolveRestaurantId($request);
        if (!$restaurantId) {
            return ApiResponse::error('Restaurant context required.', null, 422);
        }

        $settings = RestaurantSetting::where('restaurant_id', $restaurantId)
            ->with('todaySpecial')
            ->first();

        if (!$settings) {
            return ApiResponse::success(null);
        }

        return ApiResponse::success($settings);
    }

    /**
     * PUT /api/v1/restaurant-settings
     */
    public function upsert(Request $request): JsonResponse
    {
        $restaurantId = $this->resolveRestaurantId($request);
        if (!$restaurantId) {
            return ApiResponse::error('Restaurant context required.', null, 422);
        }

        $validated = $request->validate([
            'name'            => ['nullable', 'string', 'max:255'],
            'motto'           => ['nullable', 'string', 'max:255'],
            'banner_message'  => ['nullable', 'string', 'max:1000'],
            'today_special_id'=> ['nullable', 'exists:menu_items,id'],
            'brand_colors'    => ['nullable', 'array'],
            'brand_colors.primary'   => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_colors.secondary' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_colors.accent'    => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_colors.success'   => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_colors.danger'    => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:500'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.monday'    => ['nullable', 'array'],
            'operating_hours.tuesday'   => ['nullable', 'array'],
            'operating_hours.wednesday' => ['nullable', 'array'],
            'operating_hours.thursday'  => ['nullable', 'array'],
            'operating_hours.friday'    => ['nullable', 'array'],
            'operating_hours.saturday'  => ['nullable', 'array'],
            'operating_hours.sunday'    => ['nullable', 'array'],
        ]);

        if (!empty($validated['today_special_id'])) {
            $item = MenuItem::where('id', $validated['today_special_id'])
                ->where('restaurant_id', $restaurantId)
                ->first();
            if (!$item) {
                return ApiResponse::error('Menu item not found for this restaurant.', null, 422);
            }
        }

        if (!empty($validated['name'])) {
            Restaurant::where('id', $restaurantId)->update(['name' => $validated['name']]);
            unset($validated['name']);
        }

        $settings = RestaurantSetting::updateOrCreate(
            ['restaurant_id' => $restaurantId],
            $validated
        );

        $settings->load('todaySpecial');

        return ApiResponse::success($settings, 'Settings saved');
    }

    /**
     * POST /api/v1/restaurant-settings/logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $restaurantId = $this->resolveRestaurantId($request);
        if (!$restaurantId) {
            return ApiResponse::error('Restaurant context required.', null, 422);
        }

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $file = $request->file('logo');
        $filename = 'logo-' . $restaurantId . '-' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('logos', $filename, 'public');

        $settings = RestaurantSetting::updateOrCreate(
            ['restaurant_id' => $restaurantId],
            ['logo_path' => Storage::disk('public')->url($path)]
        );

        return ApiResponse::success([
            'logo_url' => $settings->logo_path,
        ], 'Logo uploaded');
    }

    /**
     * GET /api/v1/public/restaurants/{id}/settings
     */
    public function publicShow(int $id): JsonResponse
    {
        $restaurant = Restaurant::where('id', $id)->active()->first();
        if (!$restaurant) {
            return ApiResponse::notFound('Restaurant not found.');
        }

        $settings = RestaurantSetting::where('restaurant_id', $id)
            ->with('todaySpecial')
            ->first();

        return ApiResponse::success([
            'restaurant' => [
                'id'    => $restaurant->id,
                'name'  => $restaurant->name,
                'slug'  => $restaurant->slug,
                'image_url' => $restaurant->image_url,
            ],
            'settings' => $settings,
        ]);
    }
}
