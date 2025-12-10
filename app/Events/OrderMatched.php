<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $buyOrder,
        public readonly Order $sellOrder
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->buyOrder->user_id),
            new PrivateChannel('user.' . $this->sellOrder->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    public function broadcastWith(): array
    {
        return [
            'buy_order' => [
                'id' => $this->buyOrder->id,
                'status' => $this->buyOrder->status,
                'symbol' => $this->buyOrder->symbol,
                'side' => $this->buyOrder->side,
                'price' => $this->buyOrder->price,
                'amount' => $this->buyOrder->amount,
            ],
            'sell_order' => [
                'id' => $this->sellOrder->id,
                'status' => $this->sellOrder->status,
                'symbol' => $this->sellOrder->symbol,
                'side' => $this->sellOrder->side,
                'price' => $this->sellOrder->price,
                'amount' => $this->sellOrder->amount,
            ],
        ];
    }
}


