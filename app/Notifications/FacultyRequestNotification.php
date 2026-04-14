<?php

namespace App\Notifications;

use App\Models\Faculty;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class FacultyRequestNotification extends Notification
{
    use Queueable;

    public $faculty;
    public $senderName;

    /**
     * Pass the faculty record and the name of the Dean/OIC
     */
    public function __construct(Faculty $faculty, $senderName)
    {
        $this->faculty = $faculty;
        $this->senderName = $senderName;
    }

    /**
     * We use 'database' for dashboard alerts. 
     * You can keep 'mail' if you want actual emails sent too.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * This data gets saved in the 'data' column of your notifications table.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'faculty_id' => $this->faculty->id,
            'faculty_name' => $this->faculty->full_name,
            'requested_by' => $this->senderName,
            'message' => "{$this->senderName} submitted a new faculty request for {$this->faculty->full_name}.",
            'type' => 'faculty_request',
            'link' => route('faculty.index'), // Ensure your route name matches
        ];
    }

    /**
     * General array representation (backup)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'faculty_id' => $this->faculty->id,
            'message' => 'New faculty approval required.',
        ];
    }
}