<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitRequestNotification extends Notification
{
    use Queueable;

    public $visit;

    /**
     * Create a new notification instance.
     */
    public function __construct($visit)
    {
        $this->visit = $visit;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $status = $this->visit->status;
        $message = "New visit request for Unit " . $this->visit->unit_number . " from " . $this->visit->visitor->name;
        
        if ($status === 'Checked In') {
            $message = "Visitor " . $this->visit->visitor->name . " has arrived and checked in for Unit " . $this->visit->unit_number;
        }

        return [
            'visit_id' => $this->visit->id,
            'visitor_name' => $this->visit->visitor->name,
            'unit_number' => $this->visit->unit_number,
            'purpose' => $this->visit->purpose,
            'message' => $message,
            'type' => $status === 'Checked In' ? 'visitor_arrival' : 'visit_request',
            'status' => $status,
            'time' => now()->toIso8601String()
        ];
    }
}
