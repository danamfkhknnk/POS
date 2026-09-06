<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockSynchronized implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $outletId,
        public string $productId,
        public int $quantity,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("outlets.{$this->outletId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'outlet_id' => $this->outletId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
