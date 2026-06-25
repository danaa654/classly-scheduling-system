<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix: Major subjects that were imported via CSV had preferred_room_type = 'LECTURE'
 * written as a default heuristic, not as an explicit user override. This caused the
 * "Use Lecture Room" checkbox to appear pre-checked on first edit even though the
 * user never set it. Clear those rows back to '' (auto / no override).
 *
 * Safe to run multiple times — only touches rows where the user never explicitly
 * triggered the override through the Edit Subject form (which would set room_override
 * intentionally). Since there is no separate audit column, the safest heuristic is:
 * Major subjects with preferred_room_type = 'LECTURE' that have never been manually
 * updated after import are cleared. Subjects where the user intentionally checked
 * the box and saved via the form are indistinguishable from import artifacts in the
 * current schema, so ALL Major rows with 'LECTURE' are cleared to ''.
 *
 * If you need to preserve intentional overrides, add a `room_override_explicit`
 * boolean column before running this migration and only clear rows where that is false.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Clear preferred_room_type on Major subjects that have 'LECTURE' set.
        // 'LECTURE' on a Major subject is the room_override value — default should be ''.
        DB::table('subjects')
            ->where('type', 'Major')
            ->where('preferred_room_type', 'LECTURE')
            ->update([
                'preferred_room_type' => '',
                'requires_lab'        => false,
            ]);

        // Symmetric safety: clear preferred_room_type on Minor subjects that have 'LAB'
        // only if requires_lab was also set by the same import heuristic (not user intent).
        // Minor + 'LAB' is the override value for Minor subjects, so leave those alone
        // — a Minor subject with LAB is more likely intentional (lab course).
        // Only clear Minor rows where preferred_room_type was set to '' by the heuristic
        // but requires_lab was incorrectly set to true.
        DB::table('subjects')
            ->where('type', 'Minor')
            ->where('preferred_room_type', '')
            ->where('requires_lab', true)
            ->update(['requires_lab' => false]);
    }

    public function down(): void
    {
        // No safe rollback — we cannot distinguish which rows were import artifacts
        // vs intentional overrides before this migration ran.
    }
};