<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On; // Required for the modern refresh logic

class NotificationCenter extends Component
{
    // The collection of notifications displayed in the dropdown
    public $notifications = [];

    /**
     * Listeners for Real-time updates.
     * Includes Echo for broadcasting and local dispatchers for Rooms/Faculty.
     */
    protected $listeners = [
        'echo:notifications,NotificationSent' => 'loadNotifications',
    ];

    /**
     * Refreshes the notification list whenever faculty or rooms are updated.
     * This handles the scenarios where Deans or Registrars perform bulk actions.
     */
    #[On('facultyUpdated')]
    #[On('roomImported')]
    public function loadNotifications()
    {
        // Fetches both Faculty and Room notifications from the database
        // Updated to latest() to ensure the most recent actions are at the top
        $this->notifications = auth()->user()->notifications()
            ->latest()
            ->take(10) 
            ->get();
    }

    /**
     * Initialize the component by loading current notifications.
     */
    public function mount()
    {
        $this->loadNotifications();
    }

    /**
     * Marks a single notification as read and refreshes the list.
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications(); 
        }
    }

    /**
     * Permanently deletes a single notification from the database.
     */
    public function deleteNotification($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->delete();
            $this->loadNotifications(); 
        }
    }

    /**
     * Bulk deletes all notifications that have already been read.
     */
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

    /**
     * Marks every unread notification as read.
     */
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