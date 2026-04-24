<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class FacultyRequestNotification extends Notification
{
    use Queueable;

    public $facultyName;
    public $senderName;
    public $type; // Matches your logic: 'pending', 'approved', 'rejected', 'deleted', 'bulk_added', 'edited'

    /**
     * @param mixed $faculty The faculty record OR the faculty name as a string (for deletions/imports)
     * @param string $senderName Name of the person performing the action
     * @param string $type The action type
     */
    public function __construct($faculty, $senderName, $type = 'pending')
    {
        // Object check: ensures we don't crash if the model is already deleted or passed as a string
        if (is_object($faculty)) {
            $this->facultyName = $faculty->full_name ?? $faculty->name ?? 'Unknown Faculty';
        } else {
            $this->facultyName = $faculty;
        }

        $this->senderName = $senderName;
        $this->type = $type;
    }

    /**
     * Store in the 'notifications' table for the UI Bell icon.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * This data is stored in the 'data' column of your 'notifications' table.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_name'  => $this->senderName,
            'faculty_name' => $this->facultyName,
            'type'         => $this->type, 
            'message'      => $this->generateMessage(),
            'sent_at'      => now()->toDateTimeString(),
        ];
    }

    /**
     * Generates a professional message based on the scenario for your Capstone Audit.
     */
    private function generateMessage(): string
    {
        return match($this->type) {
            'pending'    => 'submitted a new faculty registration request for',
            'approved'   => 'approved and verified the faculty record of',
            'rejected'   => 'declined the registration request for',
            'deleted'    => 'removed the faculty record of',
            'bulk_added' => 'imported a batch of faculty members to',
            'edited'     => 'modified the professional details of',
            default      => 'performed an administrative action on the record of',
        };
    }
}


