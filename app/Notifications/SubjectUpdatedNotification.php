<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubjectUpdatedNotification extends Notification
{
    use Queueable;

    protected $subject;
    protected $actionType;

    public function __construct($subject, $actionType = 'updated')
    {
        $this->subject = $subject;
        $this->actionType = $actionType;
    }

    public function via($notifiable)
    {
        // This tells Laravel to save it to the 'notifications' table
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => $this->actionType, // 'created', 'updated', or 'deleted'
            'sender_name' => auth()->user()->name,
            'message' => "has {$this->actionType} the subject: {$this->subject->subject_code}",
            'faculty_name' => $this->subject->subject_description, 
        ];
    }
}