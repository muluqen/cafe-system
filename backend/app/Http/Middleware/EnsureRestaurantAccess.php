<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user belongs to the restaurant being accessed
 * and that the restaurant status is active.
 */
class EnsureRestaurantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized();
        }

        if ($user->role !== 'restaurant') {
            return $next($request);
        }

        $restaurantId = $user->restaurant_id;

        if (!$restaurantId) {
            return ApiResponse::error('Restaurant account is not linked to a restaurant.', null, 403);
        }

        $restaurant = Restaurant::find($restaurantId);

        if ($restaurant && $restaurant->status !== 'active') {
            $message = match ($restaurant->status) {
                'pending'    => 'Your restaurant is pending approval. Please wait for a super admin to approve your account.',
                'suspended'  => 'Your restaurant has been suspended. Reason: ' . ($restaurant->rejection_reason ?? 'No reason provided.'),
                default      => 'Your restaurant account is not active.',
            };

            return ApiResponse::error($message, null, 403);
        }

        $targetRestaurantId = $request->route('restaurant')
            ?? $request->header('X-Restaurant-Id')
            ?? $request->query('restaurant_id');

        if ($targetRestaurantId && (int) $targetRestaurantId !== (int) $restaurantId) {
            return ApiResponse::error('You do not have access to this restaurant.', null, 403);
        }

        return $next($request);
    }
}
