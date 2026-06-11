<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('kds.' . $this->order->restaurant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        $this->order->load(['orderItems', 'orderItems.menuItem', 'table']);

        return [
            'id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'table_id' => $this->order->table_id,
            'table_name' => $this->order->table?->name ?? 'Takeaway',
            'notes' => $this->order->notes,
            'placed_at' => $this->order->placed_at?->toISOString(),
            'items' => $this->order->orderItems->map(fn ($item) => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'routing_station' => $item->routing_station,
                'status' => $item->status,
            ]),
        ];
    }
}
