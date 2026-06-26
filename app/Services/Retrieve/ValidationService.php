<?php

namespace App\Services\Retrieve;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Validates a single archived schedule record against the current semester
 * configuration and returns a list of human-readable "Needs Review" reasons.
 *
 * An empty array means the record is fully valid and can be imported as-is.
 *
 * The philosophy: NEVER silently modify or drop a schedule.
 * If something is off, import it and mark it "needs_review" with a reason.
 */
final class ValidationService
{
    /**
     * Validate one archived schedule record.
     *
     * @param  object      $schedule       Archived schedule row (stdClass from DB / Eloquent model)
     * @param  array       $period         Current semester period array from Setting::getAcademicPeriod()
     * @param  Collection  $validFacultyIds  Flipped collection of approved faculty IDs
     * @param  Collection  $validRoomIds     Flipped collection of existing room IDs
     * @return string[]  List of reason strings; empty = valid
     */
    public function validateScheduleRecord(
        object     $schedule,
        array      $period,
        Collection $validFacultyIds,
        Collection $validRoomIds,
    ): array {
        $reasons = [];

        $settings   = Setting::getScheduleSettings();
        $activeDays = array_map('strtolower', (array) ($settings['active_days'] ?? []));

        // ── Day validation ────────────────────────────────────────────────────
        if ($schedule->day && ! in_array(strtolower($schedule->day), $activeDays, true)) {
            $reasons[] = 'Inactive day: ' . ucfirst($schedule->day);
        }

        // ── Time-window validation ────────────────────────────────────────────
        if ($schedule->start_time && $schedule->end_time) {
            $start       = Carbon::parse($schedule->start_time);
            $end         = Carbon::parse($schedule->end_time);
            $configStart = Carbon::parse($settings['start_time']);
            $configEnd   = Carbon::parse($settings['end_time']);

            if ($start->lt($configStart)) {
                $reasons[] = sprintf(
                    'Outside configured hours: starts at %s (configured from %s)',
                    $start->format('g:i A'),
                    $configStart->format('g:i A'),
                );
            }

            if ($end->gt($configEnd)) {
                $reasons[] = sprintf(
                    'Outside configured hours: ends at %s (configured until %s)',
                    $end->format('g:i A'),
                    $configEnd->format('g:i A'),
                );
            }

            if ($start->gte($end)) {
                $reasons[] = 'Invalid time range: start time is not before end time';
            }
        }

        // ── Faculty validation ────────────────────────────────────────────────
        if ($schedule->faculty_id && ! $validFacultyIds->has($schedule->faculty_id)) {
            $reasons[] = 'Missing or inactive faculty (ID: ' . $schedule->faculty_id . ')';
        }

        // ── Room validation ───────────────────────────────────────────────────
        if ($schedule->room_id && ! $validRoomIds->has($schedule->room_id)) {
            $reasons[] = 'Missing room (ID: ' . $schedule->room_id . ')';
        }

        return $reasons;
    }
}