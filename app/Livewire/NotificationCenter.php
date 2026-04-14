<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationCenter extends Component
{
    public function getNotificationsProperty()
    {
        return auth()->user()->unreadNotifications;
    }

    public function markAsRead($notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            return redirect($notification->data['link']);
        }
    }

    public function render()
    {
        return view('livewire.notification-center');
    }
}