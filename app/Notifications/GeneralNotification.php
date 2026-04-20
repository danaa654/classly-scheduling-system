<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneralNotification extends Notification
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     * @param array $data Expects: title, message, type, url, sender_name
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for the database.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title'       => $this->data['title'] ?? 'System Update',
            'message'     => $this->data['message'] ?? '',
            'type'        => $this->data['type'] ?? 'general',
            'url'         => $this->data['url'] ?? '#',
            'sender_name' => $this->data['sender_name'] ?? 'System',
            'created_at'  => now()->toDateTimeString(), // Stores the real-time date
        ];
    }

    /**
     * Fallback for other channels
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}