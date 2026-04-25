<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On; 

class NotificationCenter extends Component
{
    public $notifications = [];

    protected $listeners = [
        'echo:notifications,NotificationSent' => 'loadNotifications',
    ];

    /**
     * Listen for any registry changes (Update, Import, Delete, Room changes)
     */
    #[On('facultyUpdated')]
    #[On('facultyDeleted')] // Catching the new delete events
    #[On('roomImported')]
    #[On('subjectUpdated')] // Add this for Subjects
    #[On('notify')]
    
    public function loadNotifications()
    {
        // Get the latest 10 notifications for the authenticated user
        $this->notifications = auth()->user()->notifications()
            ->latest()
            ->take(10) 
            ->get();
    }

    public function mount()
    {
        $this->loadNotifications();
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications(); 
        }
    }

    public function deleteNotification($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->delete();
            $this->loadNotifications(); 
        }
    }

    public function deleteAllRead()
    {
        auth()->user()->readNotifications()->delete();
        $this->loadNotifications();
        
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Archive Cleared',
            'detail' => 'All read notifications have been removed.'
        ]);
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