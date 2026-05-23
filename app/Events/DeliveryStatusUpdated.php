<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $logId,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("delivery-log.{$this->logId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'delivery.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'log_id' => $this->logId,
            'status' => $this->newStatus,
        ];
    }
}
