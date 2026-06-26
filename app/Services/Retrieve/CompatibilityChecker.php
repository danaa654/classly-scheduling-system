<?php

namespace App\Services\Retrieve;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Compares an archived semester's configuration and schedule data against the
 * current semester configuration to surface incompatibilities BEFORE cloning.
 *
 * Only invoked for the COMPLETE_CLONE mode (the only mode that copies timeslots).
 */
final class CompatibilityChecker
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Run the full compatibility analysis for the given archive batch.
     *
     * Returns a CompatibilityReport describing every class of difference
     * found.  An empty report (isCompatible() === true) means the clone can
     * proceed immediately without surfacing a warning dialog.
     */
    public function check(string $archiveBatchId): CompatibilityReport
    {
        $archived = $this->archivedConfig($archiveBatchId);
        $current  = $this->currentConfig();

        return new CompatibilityReport(
            configDifferences:   $this->diffConfig($archived, $current),
            inactiveDays:        $this->detectInactiveDays($archived, $current),
            missingFacultyIds:   $this->detectMissingFaculty($archiveBatchId),
            missingRoomIds:      $this->detectMissingRooms($archiveBatchId),
            outOfBoundsSchedules: $this->detectOutOfBounds($archiveBatchId, $current),
        );
    }

    // ── Config helpers ────────────────────────────────────────────────────────

    /**
     * Reconstruct the archived semester's schedule configuration.
     *
     * We first look for explicit settings stored in the archive record,
     * then fall back to deriving them from the earliest/latest schedule
     * times in the archive batch.
     */
    private function archivedConfig(string $archiveBatchId): array
    {
        // Try to read directly from schedule_archives if the columns exist
        $row = DB::table('schedule_archives')
            ->where('archive_batch_id', $archiveBatchId)
            ->first();

        $startTime  = $row?->start_time ?? null;
        $endTime    = $row?->end_time   ?? null;
        $activeDays = $row?->active_days ?? null;

        if ($activeDays && is_string($activeDays)) {
            $activeDays = json_decode($activeDays, true);
        }

        // Fall back: derive from actual archived schedule records
        if (! $startTime || ! $endTime) {
            $times = Schedule::where('archive_batch', $archiveBatchId)
                ->whereNotNull('start_time')
                ->selectRaw('MIN(start_time) as earliest, MAX(end_time) as latest')
                ->first();

            $startTime = $startTime ?? ($times?->earliest ? Carbon::parse($times->earliest)->format('H:i') : '07:00');
            $endTime   = $endTime   ?? ($times?->latest   ? Carbon::parse($times->latest)->format('H:i')   : '20:00');
        }

        if (! $activeDays) {
            $activeDays = Schedule::where('archive_batch', $archiveBatchId)
                ->whereNotNull('day')
                ->distinct()
                ->pluck('day')
                ->sort()
                ->values()
                ->all();
        }

        return [
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'active_days' => $activeDays,
        ];
    }

    /** Read the LIVE semester configuration from Settings. */
    private function currentConfig(): array
    {
        $settings = Setting::getScheduleSettings();

        return [
            'start_time'  => $settings['start_time'],
            'end_time'    => $settings['end_time'],
            'active_days' => $settings['active_days'],
        ];
    }

    // ── Diff helpers ──────────────────────────────────────────────────────────

    /** Build a list of human-readable configuration field differences. */
    private function diffConfig(array $archived, array $current): array
    {
        $diffs = [];

        foreach (['start_time' => 'Start Time', 'end_time' => 'End Time'] as $key => $label) {
            $a = $archived[$key] ?? null;
            $c = $current[$key]  ?? null;

            if ($a && $c && Carbon::parse($a)->format('H:i') !== Carbon::parse($c)->format('H:i')) {
                $diffs[] = [
                    'field'    => $key,
                    'label'    => $label,
                    'archived' => Carbon::parse($a)->format('g:i A'),
                    'current'  => Carbon::parse($c)->format('g:i A'),
                    'status'   => 'Different',
                ];
            }
        }

        return $diffs;
    }

    /**
     * Days that appear in the archive but are NOT in the current active-days list.
     * Returns an array of day-name strings.
     */
    private function detectInactiveDays(array $archived, array $current): array
    {
        $archivedDays = array_map('strtolower', (array) ($archived['active_days'] ?? []));
        $currentDays  = array_map('strtolower', (array) ($current['active_days']  ?? []));

        return array_values(
            array_filter($archivedDays, fn ($day) => ! in_array($day, $currentDays, true))
        );
    }

    /**
     * Faculty IDs from the archive that no longer exist (deleted) or are
     * not approved in the system.
     */
    private function detectMissingFaculty(string $archiveBatchId): array
    {
        $archivedFacultyIds = Schedule::where('archive_batch', $archiveBatchId)
            ->whereNotNull('faculty_id')
            ->distinct()
            ->pluck('faculty_id')
            ->all();

        if (empty($archivedFacultyIds)) {
            return [];
        }

        $existingIds = Faculty::whereIn('id', $archivedFacultyIds)
            ->where('status', 'approved')
            ->pluck('id')
            ->all();

        return array_values(array_diff($archivedFacultyIds, $existingIds));
    }

    /**
     * Room IDs from the archive that no longer exist in the rooms table.
     */
    private function detectMissingRooms(string $archiveBatchId): array
    {
        $archivedRoomIds = Schedule::where('archive_batch', $archiveBatchId)
            ->whereNotNull('room_id')
            ->distinct()
            ->pluck('room_id')
            ->all();

        if (empty($archivedRoomIds)) {
            return [];
        }

        $existingIds = Room::whereIn('id', $archivedRoomIds)->pluck('id')->all();

        return array_values(array_diff($archivedRoomIds, $existingIds));
    }

    /**
     * Archived schedule records whose time slot falls outside the current
     * semester's configured start / end window.
     *
     * Returns array of ['schedule_id', 'day', 'start_time', 'end_time', 'reason'].
     */
    private function detectOutOfBounds(string $archiveBatchId, array $current): array
    {
        $configStart = Carbon::parse($current['start_time']);
        $configEnd   = Carbon::parse($current['end_time']);
        $activeDays  = array_map('strtolower', (array) ($current['active_days'] ?? []));

        $schedules = Schedule::where('archive_batch', $archiveBatchId)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get(['id', 'day', 'start_time', 'end_time']);

        $issues = [];

        foreach ($schedules as $sched) {
            $reasons = [];

            if ($sched->day && ! in_array(strtolower($sched->day), $activeDays, true)) {
                $reasons[] = 'Inactive day ('.ucfirst($sched->day).')';
            }

            if ($sched->start_time) {
                $start = Carbon::parse($sched->start_time);
                $end   = Carbon::parse($sched->end_time);

                if ($start->lt($configStart)) {
                    $reasons[] = 'Starts before configured hours ('.$start->format('g:i A').')';
                }

                if ($end->gt($configEnd)) {
                    $reasons[] = 'Ends after configured hours ('.$end->format('g:i A').')';
                }
            }

            if ($reasons !== []) {
                $issues[] = [
                    'schedule_id' => $sched->id,
                    'day'         => $sched->day,
                    'start_time'  => $sched->start_time
                        ? Carbon::parse($sched->start_time)->format('g:i A')
                        : null,
                    'end_time'    => $sched->end_time
                        ? Carbon::parse($sched->end_time)->format('g:i A')
                        : null,
                    'reasons'     => $reasons,
                ];
            }
        }

        return $issues;
    }
}