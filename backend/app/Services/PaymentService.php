<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles payment events, receipts, and payment status tracking.
 */
class PaymentService
{
    /**
     * List payment events for a restaurant.
     *
     * @param  int  $restaurantId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listPayments(int $restaurantId, int $perPage = 20): LengthAwarePaginator
    {
        return PaymentEvent::query()
            ->whereHas('order', function ($builder) use ($restaurantId): void {
                $builder->where('restaurant_id', $restaurantId);
            })
            ->with(['order'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a payment event for an order.
     *
     * @param  array<string, mixed> $data
     * @return PaymentEvent
     */
    public function createPayment(array $data): PaymentEvent
    {
        $payment = PaymentEvent::query()->create($data);

        if (($data['status'] ?? '') === 'completed') {
            $this->markOrderPaid($data['order_id']);
        }

        return $payment;
    }

    /**
     * Update a payment event.
     *
     * @param  PaymentEvent         $payment
     * @param  array<string, mixed> $data
     * @return PaymentEvent
     */
    public function updatePayment(PaymentEvent $payment, array $data): PaymentEvent
    {
        $payment->update($data);

        if (($data['status'] ?? '') === 'completed') {
            $this->markOrderPaid($payment->order_id);
        }

        return $payment;
    }

    /**
     * Mark an order as paid.
     *
     * @param  int $orderId
     * @return void
     */
    private function markOrderPaid(int $orderId): void
    {
        $order = Order::find($orderId);
        if ($order && $order->status === 'pending') {
            $order->update(['status' => 'paid']);
        }
    }
}
