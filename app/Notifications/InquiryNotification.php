<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InquiryNotification extends Notification
{
    use Queueable;

    public Inquiry $inquiry;
    public string $event;    // 'created' | 'admin_reply' | 'user_reply' | 'resolved'

    /**
     * Create a new notification instance.
     *
     * @param Inquiry $inquiry  The inquiry model
     * @param string  $event    The event that triggered this notification
     */
    public function __construct(Inquiry $inquiry, string $event)
    {
        $this->inquiry = $inquiry;
        $this->event   = $event;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $subject = $this->inquiry->subject;
        $name    = $this->inquiry->name;

        $message = match ($this->event) {
            'created'     => "New inquiry submitted by {$name}: \"{$subject}\"",
            'admin_reply' => "Admin replied to your inquiry: \"{$subject}\"",
            'user_reply'  => "{$name} replied to inquiry: \"{$subject}\"",
            'resolved'    => "Inquiry \"{$subject}\" has been marked as resolved by {$name}.",
            default       => "Inquiry update: \"{$subject}\"",
        };

        return [
            'inquiry_id' => $this->inquiry->id,
            'subject'    => $subject,
            'name'       => $name,
            'message'    => $message,
            'type'       => 'inquiry_' . $this->event,
            'status'     => $this->inquiry->status,
            'time'       => now()->toIso8601String(),
        ];
    }
}
