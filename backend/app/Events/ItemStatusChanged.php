<?php

namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OrderItem $item,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('kds.' . $this->item->order->restaurant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'item.status';
    }

    public function broadcastWith(): array
    {
        return [
            'item_id'    => $this->item->id,
            'order_id'   => $this->item->order_id,
            'item_name'  => $this->item->item_name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
