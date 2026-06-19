<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;

class LogUserLogout
{
    public function __construct()
    {
        //
    }

    public function handle(Logout $event): void
    {
        if ($event->user) {
            // Find the last login log entry for this specific user that hasn't logged out yet
            DB::table('login_logs')
                ->where('user_id', $event->user->id)
                ->whereNull('logout_at')
                ->orderBy('login_at', 'desc')
                ->limit(1)
                ->update([
                    'logout_at'  => now(),
                    'updated_at' => now(),
                ]);
        }
    }
}