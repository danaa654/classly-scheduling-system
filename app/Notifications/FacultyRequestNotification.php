<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacultyRequestNotification extends Notification
{
    use Queueable;

    public $facultyName;
    public $senderName;
    public $type; // 'pending', 'approved', 'rejected', 'deleted', 'bulk_added'

    /**
     * @param mixed $faculty The faculty record OR the faculty name as a string (for deletions/imports)
     * @param string $senderName Name of the person performing the action
     * @param string $type The action type (matches your color logic)
     */
    public function __construct($faculty, $senderName, $type = 'pending')
    {
        // If $faculty is an object, get the name. If it's a string (like after deletion), use it directly.
        $this->facultyName = is_object($faculty) ? ($faculty->full_name ?? $faculty->name) : $faculty;
        $this->senderName = $senderName;
        $this->type = $type;
    }

    /**
     * Use 'database' for the Notification Center.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'sender_name'  => $this->senderName,
            'faculty_name' => $this->facultyName,
            'type'         => $this->type, // This triggers Red or Blue in your Blade
            'message'      => $this->generateMessage(),
        ];
    }

    /**
     * Generates a professional message based on the scenario.
     */
    private function generateMessage(): string
    {
        return match($this->type) {
            'pending'    => 'submitted a new faculty request for',
            'approved'   => 'approved the faculty request for',
            'rejected'   => 'declined the faculty request for',
            'deleted'    => 'permanently removed the rejected record of',
            'bulk_added' => 'imported a new faculty list to',
            'edited'     => 'updated the faculty details for',
            default      => 'updated the registry for',
        };
    }
}   