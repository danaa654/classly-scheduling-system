<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the three system-readiness keys into the settings table.
 *
 * - system_ready      : '0' or '1' — whether the semester is live for all roles
 * - system_ready_at   : ISO-8601 timestamp of when it was last marked ready
 * - system_ready_by   : user ID of who marked it ready
 *
 * These are key/value rows — no schema change is needed because the settings
 * table already uses an open-ended `key` / `value` pair design.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        $now  = now();
        $keys = ['system_ready', 'system_ready_at', 'system_ready_by'];

        // Only insert rows that don't already exist.
        $existing = DB::table('settings')
            ->whereIn('key', $keys)
            ->pluck('key')
            ->all();

        $toInsert = [];

        if (! in_array('system_ready', $existing, true)) {
            $toInsert[] = [
                'key'        => 'system_ready',
                'value'      => '0',          // New semesters start as NOT ready
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! in_array('system_ready_at', $existing, true)) {
            $toInsert[] = [
                'key'        => 'system_ready_at',
                'value'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! in_array('system_ready_by', $existing, true)) {
            $toInsert[] = [
                'key'        => 'system_ready_by',
                'value'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($toInsert !== []) {
            DB::table('settings')->insert($toInsert);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['system_ready', 'system_ready_at', 'system_ready_by'])
            ->delete();
    }
};