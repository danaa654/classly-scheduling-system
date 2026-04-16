<?php
namespace App\Traits;

trait HandlesNotifications {
    public function markAsRead($id) {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) { $notification->markAsRead(); }
    }
}