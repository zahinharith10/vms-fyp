<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $visitId,
        public string $newStatus,
        public ?int $parkingLotNumber = null,
        public ?string $unitNumber = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("visit.{$this->visitId}"),
            new Channel('guard.updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visit.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'visit_id'          => $this->visitId,
            'status'            => $this->newStatus,
            'parking_lot_number'=> $this->parkingLotNumber,
            'unit_number'       => $this->unitNumber,
        ];
    }
}
