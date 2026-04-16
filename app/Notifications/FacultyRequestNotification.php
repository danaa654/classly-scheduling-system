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
     * @param string $senderName Name of the person who performed the action
     * @param string $status The current status (pending, approved, rejected, deleted)
     */
    public function __construct(Faculty $faculty, $senderName, $status = 'pending')
    {
        $this->faculty = $faculty;
        $this->senderName = $senderName;
        $this->status = $status;
    }

    /**
     * Use 'database' to store notifications in the system table.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * This data is what actually shows up in your notification-center.blade.php
     */
    public function toArray(object $notifiable): array
{
    $message = '';
    // Logic for Registrar/Admin
    if (in_array($notifiable->role, ['admin', 'registrar'])) {
       $message = match($this->status) {
        'pending'  => "{$this->senderName} submitted a new faculty request for {$this->faculty->full_name}.",
        'approved' => "The Registrar has approved the record for {$this->faculty->full_name}.",
        'rejected' => "The request for {$this->faculty->full_name} was declined by the Registrar.",
        'edited'   => "The Registrar updated the faculty record of {$this->faculty->full_name}.",
        'deleted'  => "The faculty record for {$this->faculty->full_name} was removed by the Registrar.",
        default    => "Update on faculty: {$this->faculty->full_name}",
    };
    } 
    // Logic for Deans
    else {
        $message = match($this->status) {
            'approved' => "The Registrar has approved your faculty request for {$this->faculty->full_name}.",
            'rejected' => "Your request for {$this->faculty->full_name} was rejected by the Registrar.",
            'edited'   => "The details for {$this->faculty->full_name} have been updated.",
            'deleted'  => "The faculty record for {$this->faculty->full_name} has been deleted.",
            default    => "There is an update on {$this->faculty->full_name}."
        };
    }

    return [
        'faculty_id' => $this->faculty->id,
        'message' => $message,
        'status' => $this->status,
    ];
}
}