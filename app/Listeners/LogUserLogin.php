<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        $userId = $event->user->id;

        // 🛡️ Safety check: Has this user logged in within the last 5 seconds?
        $recentLogExists = DB::table('login_logs')
            ->where('user_id', $userId)
            ->where('login_at', '>=', now()->subSeconds(5))
            ->exists();

        // Only insert if it is NOT a duplicate double-fire event
        if (!$recentLogExists) {
            DB::table('login_logs')->insert([
                'user_id'    => $userId,
                'ip_address' => request()->ip(),
                'login_at'   => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}