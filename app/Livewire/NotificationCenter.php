<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationCenter extends Component
{
    // Define the property so the view can access it
    public $notifications = [];

    // Listeners for both Faculty (Echo/Broadcast) and Room (Local Dispatch)
    protected $listeners = [
        'echo:notifications,NotificationSent' => 'loadNotifications',
        'roomImported' => 'loadNotifications', 
        'facultyUpdated' => 'loadNotifications', // Ensuring faculty logic is preserved
    ];

    // Runs when the component is first loaded
    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        // Fetches both Faculty and Room notifications from the database
        $this->notifications = auth()->user()->notifications()
            ->latest()
            ->take(10) // Increased to 10 so the Dean sees more history
            ->get();
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications(); // Refresh list after marking read
        }
    }

    public function deleteNotification($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->delete();
            $this->loadNotifications(); // Refresh list after deleting
        }
    }

    public function deleteAllRead()
    {
        auth()->user()->readNotifications()->delete();
        $this->loadNotifications();
        session()->flash('message', 'Cleared all read notifications.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notification-center');
    }
}