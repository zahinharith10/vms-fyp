<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewVisitRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $visitId,
        public string $visitorName,
        public string $unitNumber,
        public string $purpose,
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast to the specific resident unit channel
        $safeUnit = str_replace(['/', '\\', ' '], '-', $this->unitNumber);
        return [
            new Channel("unit.{$safeUnit}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visit.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'visit_id'     => $this->visitId,
            'visitor_name' => $this->visitorName,
            'unit_number'  => $this->unitNumber,
            'purpose'      => $this->purpose,
        ];
    }
}
