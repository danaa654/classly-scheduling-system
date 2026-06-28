<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * RoomCapacityService
 *
 * Central service for all weekly room capacity calculations.
 * Replaces every hardcoded hour constant in the application.
 *
 * ─── Two layers of capacity tracking ────────────────────────────────────────
 *   1. GLOBAL max  → derived from semester settings (day_start/end, lunch, active days)
 *      Used by: ManageRooms modal, AutoScheduleService, ScheduleConflictService
 *
 *   2. PER-ROOM current load → sum of duration_hours for subjects assigned via
 *      preferred_room_id (pre-assignment layer) OR via Schedule records (scheduled layer)
 *      Used by: ManageRooms save-validation, auto-scheduler room filtering
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * ─── Business rule ───────────────────────────────────────────────────────────
 *   A room CANNOT be assigned more subjects than its weekly capacity allows.
 *   weekly_hours_per_subject = subject.duration_hours  (NOT duration × meetings)
 *   meetings_per_week splits the block across days; it does NOT multiply room usage.
 *   Example: SA101 4h / 2x per week  →  2h Mon + 2h Wed = 4h total room time
 * ─────────────────────────────────────────────────────────────────────────────
 */
class RoomCapacityService
{
    // =========================================================================
    // GLOBAL CAPACITY (derived from settings — same for all rooms)
    // =========================================================================

    /**
     * Total available hours per room per week for the active semester.
     *
     * Formula: (day_end − day_start − lunch_duration) × count(active_days)
     *
     * @return float  e.g. 63.0
     */
    public static function getWeeklyCapacity(): float
    {
        $daily      = self::getDailyAvailableHours();
        $activeDays = count(Setting::getActiveDays());

        return round($daily * max(1, $activeDays), 2);
    }

    /**
     * Available teaching hours in a single day after deducting the lunch break.
     *
     * @return float  e.g. 10.5
     */
    public static function getDailyAvailableHours(): float
    {
        $bounds = Setting::getDayBounds();
        $lunch  = Setting::getLunchBreakTimes();

        $dayStart   = Carbon::createFromTimeString($bounds['start']);
        $dayEnd     = Carbon::createFromTimeString($bounds['end']);
        $lunchStart = Carbon::createFromTimeString($lunch['start']);
        $lunchEnd   = Carbon::createFromTimeString($lunch['end']);

        $totalMinutes = max(0, $dayStart->diffInMinutes($dayEnd, false));

        $lunchMinutes = 0;
        if ($lunchStart->lt($dayEnd) && $lunchEnd->gt($dayStart)) {
            $effectiveLunchStart = $lunchStart->max($dayStart);
            $effectiveLunchEnd   = $lunchEnd->min($dayEnd);
            $lunchMinutes        = max(0, $effectiveLunchStart->diffInMinutes($effectiveLunchEnd, false));
        }

        return round(max(0, $totalMinutes - $lunchMinutes) / 60, 2);
    }

    /**
     * Human-readable weekly capacity string, e.g. "63h".
     */
    public static function getFormattedCapacity(): string
    {
        $capacity = self::getWeeklyCapacity();

        return (fmod($capacity, 1.0) === 0.0)
            ? (int) $capacity . 'h'
            : number_format($capacity, 1) . 'h';
    }

    /**
     * Whether the given number of hours exceeds the weekly room capacity.
     */
    public static function isOverCapacity(float $hours): bool
    {
        return $hours > self::getWeeklyCapacity();
    }

    /**
     * Utilisation percentage (0–100) for the given hours value.
     */
    public static function utilizationPercent(float $hours): int
    {
        $capacity = self::getWeeklyCapacity();

        if ($capacity <= 0) {
            return 0;
        }

        return min(100, (int) round(($hours / $capacity) * 100));
    }

    /**
     * Quick summary array — handy for passing a single object to Blade views.
     *
     * @return array{daily: float, weekly: float, formatted: string, active_days: int}
     */
    public static function summary(): array
    {
        return [
            'daily'       => self::getDailyAvailableHours(),
            'weekly'      => self::getWeeklyCapacity(),
            'formatted'   => self::getFormattedCapacity(),
            'active_days' => count(Setting::getActiveDays()),
        ];
    }

    // =========================================================================
    // PER-ROOM LOAD — preferred_room_id layer (ManageRooms pre-assignment)
    // =========================================================================

    /**
     * Current weekly hours already assigned to a room via preferred_room_id.
     *
     * weekly_hours = duration_hours (NOT duration × meetings).
     * meetings_per_week only splits the block across days.
     *
     * @param  int|Room   $room             Room model or room ID.
     * @param  int[]      $excludeSubjectIds Subject IDs to exclude (e.g. the subject
     *                                       being moved away from this room).
     * @return float
     */
    public static function getCurrentRoomLoad(int|Room $room, array $excludeSubjectIds = []): float
    {
        $roomId = $room instanceof Room ? $room->id : $room;

        return (float) Subject::activeTerm()
            ->where('preferred_room_id', $roomId)
            ->when(!empty($excludeSubjectIds), fn ($q) => $q->whereNotIn('id', $excludeSubjectIds))
            ->where(function ($q) {
                $q->where('is_practicum', false)->orWhereNull('is_practicum');
            })
            ->sum('duration_hours');
    }

    /**
     * Remaining weekly hours available in a room (preferred_room_id layer).
     *
     * @param  int|Room  $room
     * @param  int[]     $excludeSubjectIds
     * @return float  Can be negative when already over capacity.
     */
    public static function getRemainingCapacity(int|Room $room, array $excludeSubjectIds = []): float
    {
        return self::getWeeklyCapacity() - self::getCurrentRoomLoad($room, $excludeSubjectIds);
    }

    /**
     * Whether a subject can be added to a room without exceeding weekly capacity.
     *
     * Excludes the subject itself from the load calculation so editing an
     * existing assignment doesn't false-positive.
     *
     * @param  Room     $room
     * @param  Subject  $subject
     * @param  int[]    $excludeSubjectIds  Additional subject IDs to exclude from load.
     * @return bool
     */
    public static function canAcceptSubject(Room $room, Subject $subject, array $excludeSubjectIds = []): bool
    {
        // Always exclude the subject being evaluated so re-assigning to the same
        // room doesn't count its own hours twice.
        $exclude   = array_unique(array_merge($excludeSubjectIds, [$subject->id]));
        $maxCap    = self::getWeeklyCapacity();
        $curLoad   = self::getCurrentRoomLoad($room, $exclude);
        $subjHours = (float) $subject->duration_hours;

        return ($curLoad + $subjHours) <= $maxCap;
    }

    /**
     * Full capacity detail array for display in Blade modals.
     *
     * @param  int|Room   $room
     * @param  float      $pendingHours       Hours being considered for addition (not yet saved).
     * @param  int[]      $excludeSubjectIds  Subject IDs already counted in $pendingHours.
     * @return array{
     *     max_capacity: float,
     *     current_load: float,
     *     pending_hours: float,
     *     projected_total: float,
     *     remaining: float,
     *     projected_remaining: float,
     *     would_exceed: bool,
     *     utilization_pct: int,
     *     projected_pct: int,
     *     formatted_max: string,
     *     formatted_current: string,
     *     formatted_remaining: string,
     * }
     */
    public static function getRoomCapacityDetails(
        int|Room $room,
        float $pendingHours = 0.0,
        array $excludeSubjectIds = []
    ): array {
        $roomId      = $room instanceof Room ? $room->id : $room;
        $maxCap      = self::getWeeklyCapacity();
        $currentLoad = self::getCurrentRoomLoad($roomId, $excludeSubjectIds);
        $projected   = $currentLoad + $pendingHours;
        $remaining   = $maxCap - $currentLoad;

        return [
            'max_capacity'        => $maxCap,
            'current_load'        => round($currentLoad, 2),
            'pending_hours'       => round($pendingHours, 2),
            'projected_total'     => round($projected, 2),
            'remaining'           => round($remaining, 2),
            'projected_remaining' => round($maxCap - $projected, 2),
            'would_exceed'        => $projected > $maxCap,
            'utilization_pct'     => self::utilizationPercent($currentLoad),
            'projected_pct'       => min(100, $maxCap > 0 ? (int) round(($projected / $maxCap) * 100) : 0),
            'formatted_max'       => self::formatHours($maxCap),
            'formatted_current'   => self::formatHours($currentLoad),
            'formatted_remaining' => self::formatHours(max(0, $remaining)),
        ];
    }

    /**
     * Detailed validation message when an assignment would exceed capacity.
     * Returns null when capacity is fine.
     *
     * @param  Room     $room
     * @param  Subject  $subject
     * @param  int[]    $excludeSubjectIds
     * @return string|null
     */
    public static function capacityViolationMessage(Room $room, Subject $subject, array $excludeSubjectIds = []): ?string
    {
        $exclude   = array_unique(array_merge($excludeSubjectIds, [$subject->id]));
        $maxCap    = self::getWeeklyCapacity();
        $curLoad   = self::getCurrentRoomLoad($room, $exclude);
        $subjHours = (float) $subject->duration_hours;
        $projected = $curLoad + $subjHours;

        if ($projected <= $maxCap) {
            return null;
        }

        return sprintf(
            'Cannot assign subject. Room capacity exceeded. '
            . 'Room: %s · Maximum: %s/wk · Current: %s/wk · Remaining: %s/wk · Selected subject: %s/wk',
            $room->room_name,
            self::formatHours($maxCap),
            self::formatHours($curLoad),
            self::formatHours(max(0, $maxCap - $curLoad)),
            self::formatHours($subjHours)
        );
    }

    // =========================================================================
    // PER-ROOM SCHEDULED LOAD — Schedule records layer (AutoScheduleService)
    // =========================================================================

    /**
     * Current weekly scheduled hours for a room based on actual Schedule records.
     *
     * Used by the auto-scheduler to prevent over-booking rooms during generation.
     * Each Schedule record stores duration_hours = single-session length, so
     * summing all sessions gives the total weekly room block.
     *
     * @param  int|Room            $room
     * @param  Collection|null     $existingSchedules  In-memory collection (fast path).
     *                                                 Falls back to a DB query when null.
     * @return float
     */
    public static function getCurrentScheduledRoomLoad(
        int|Room $room,
        ?Collection $existingSchedules = null
    ): float {
        $roomId = $room instanceof Room ? $room->id : $room;

        // Fast path: use the in-memory collection already built by the scheduler.
        if ($existingSchedules !== null) {
            return (float) $existingSchedules
                ->where('room_id', $roomId)
                ->whereNotNull('day')
                ->sum('duration_hours');
        }

        // Fallback: DB query (used outside the auto-scheduler context).
        $period = Setting::getAcademicPeriod();

        return (float) Schedule::activeTerm($period['semester'], $period['school_year'])
            ->where('room_id', $roomId)
            ->whereNotNull('day')
            ->whereHas('subject', fn ($q) =>
                $q->where('is_practicum', false)->orWhereNull('is_practicum')
            )
            ->sum('duration_hours');
    }

    /**
     * Whether a room can accept a subject's scheduled load without exceeding
     * weekly capacity — using the in-memory Schedule collection.
     *
     * @param  int|Room         $room
     * @param  Subject          $subject
     * @param  Collection|null  $existingSchedules
     * @return bool
     */
    public static function canScheduleSubjectInRoom(
        int|Room $room,
        Subject $subject,
        ?Collection $existingSchedules = null
    ): bool {
        $roomId    = $room instanceof Room ? $room->id : $room;
        $maxCap    = self::getWeeklyCapacity();
        $curLoad   = self::getCurrentScheduledRoomLoad($roomId, $existingSchedules);
        $subjHours = (float) $subject->duration_hours;

        return ($curLoad + $subjHours) <= $maxCap;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Format hours with one decimal only when non-zero, plus "h" suffix.
     * e.g. 63.0 → "63h",  63.5 → "63.5h"
     */
    public static function formatHours(float $hours): string
    {
        return (fmod(round($hours, 2), 1.0) === 0.0)
            ? (int) $hours . 'h'
            : number_format($hours, 1) . 'h';
    }
}