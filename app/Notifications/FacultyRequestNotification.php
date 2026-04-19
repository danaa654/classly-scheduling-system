<?php

namespace App\Notifications;

use App\Models\Faculty;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacultyRequestNotification extends Notification
{
    use Queueable;

    public $faculty;
    public $senderName;
    public $status;

    /**
     * @param Faculty $faculty The faculty record
     * @param string $senderName Name of the person who performed the action (e.g., "Dean of CCS")
     * @param string $status The current status (pending, approved, rejected, edited, deleted)
     */
    public function __construct(Faculty $faculty, $senderName, $status = 'pending')
    {
        $this->faculty = $faculty;
        $this->senderName = $senderName;
        $this->status = $status;
    }

    /**
     * Use 'database' to store notifications.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // 1. Determine the Action Message based on the status
        // We structure these to fit into the sentence: [Sender Name] [Message] [Faculty Name]
        $actionMessage = match($this->status) {
            'pending'  => 'submitted a new faculty request for',
            'approved' => 'approved the faculty request for',
            'rejected', 'declined' => 'declined the faculty request for',
            'edited'   => 'updated the faculty record of',
            'deleted'  => 'removed the faculty record of',
            default    => 'updated the status for',
        };

        return [
            'faculty_id'   => $this->faculty->id,
            'faculty_name' => $this->faculty->full_name ?? $this->faculty->name, 
            'sender_name'  => $this->senderName, // Uses the name passed in the constructor
            'message'      => $actionMessage,
            'status'       => $this->status,
        ];
    }
}