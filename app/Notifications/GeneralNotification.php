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
        // Saves the notification to the 'notifications' table in your database
        return ['database'];
    }

    /**
     * Get the array representation of the notification for the database.
     */
    public function toDatabase($notifiable)
{
    return [
        'title'   => $this->data['title'] ?? 'System Update',
        'message' => $this->data['message'] ?? '',
        'type'    => $this->data['type'] ?? 'general',
        'url'     => $this->data['url'] ?? '#',
        // Don't hardcode "a new member" here
    ];
}
    /**
     * Fallback for other channels (like broadcast)
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}