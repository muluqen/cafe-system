<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Responses\ApiResponse;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Handles checkout: order creation with cart, inventory deduction, routing.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Process a checkout with cart items.
     */
    public function process(CreateOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $restaurantId = (int) $request->input('restaurant_id');

        if (!$restaurantId) {
            return ApiResponse::error('Select a restaurant first to place an order.', null, 403);
        }

        try {
            $order = $this->orderService->createOrder($restaurantId, $user->id, $request->validated());

            return ApiResponse::success($order, 'Order placed', 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Some items are unavailable',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout failed: ' . $e->getMessage(), ['exception' => $e]);

            return ApiResponse::error('Checkout failed', $e->getMessage(), 500);
        }
    }
}
