<?php

namespace App\Services\Retrieve;

/**
 * Strongly-typed value object for the four semester roll-forward modes.
 *
 * Mode hierarchy (each mode is a strict superset of the one above it):
 *
 *   SUBJECTS_ONLY        → subjects (all metadata, no faculty / room / schedule)
 *   KEEP_FACULTY         → + faculty_id on subjects
 *   KEEP_FACULTY_ROOM    → + preferred_room_id on subjects
 *   COMPLETE_CLONE       → + block schedule (day / start_time / end_time / room)
 */
final class RetrieveMode
{
    public const SUBJECTS_ONLY     = 'subjects_only';
    public const KEEP_FACULTY      = 'keep_faculty';
    public const KEEP_FACULTY_ROOM = 'keep_faculty_room';
    public const COMPLETE_CLONE    = 'clone_timetable';   // keep wire-model value for BC

    public const ALL = [
        self::SUBJECTS_ONLY,
        self::KEEP_FACULTY,
        self::KEEP_FACULTY_ROOM,
        self::COMPLETE_CLONE,
    ];

    public const LABELS = [
        self::SUBJECTS_ONLY     => 'Subjects Only',
        self::KEEP_FACULTY      => 'Keep Faculty Assignments',
        self::KEEP_FACULTY_ROOM => 'Keep Faculty & Room Assignments',
        self::COMPLETE_CLONE    => 'Complete Semester Clone',
    ];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Unknown retrieve mode: {$value}");
        }
    }

    // ── Capability predicates ────────────────────────────────────────────────

    /** Whether this mode carries faculty assignments forward. */
    public function includesFaculty(): bool
    {
        return in_array($this->value, [
            self::KEEP_FACULTY,
            self::KEEP_FACULTY_ROOM,
            self::COMPLETE_CLONE,
        ], true);
    }

    /** Whether this mode carries preferred-room assignments forward. */
    public function includesRooms(): bool
    {
        return in_array($this->value, [
            self::KEEP_FACULTY_ROOM,
            self::COMPLETE_CLONE,
        ], true);
    }

    /**
     * Whether this mode creates schedule records.
     *
     * KEEP_FACULTY       → blank rows: faculty_id only (no room, no timeslot)
     * KEEP_FACULTY_ROOM  → blank rows: faculty_id + room_id (no timeslot)
     * COMPLETE_CLONE     → full rows: faculty, room, day, start_time, end_time
     */
    public function includesSchedules(): bool
    {
        return in_array($this->value, [
            self::KEEP_FACULTY,
            self::KEEP_FACULTY_ROOM,
            self::COMPLETE_CLONE,
        ], true);
    }

    /**
     * Whether this mode preserves the actual day / time slots.
     * Only COMPLETE_CLONE does — KEEP_FACULTY_ROOM creates blank schedule
     * records that carry faculty + room but no time assignment.
     */
    public function includesTimeslots(): bool
    {
        return $this->value === self::COMPLETE_CLONE;
    }

    /**
     * Whether a compatibility check against the current semester config is
     * required before proceeding.  Only the full clone is time-sensitive.
     */
    public function requiresCompatibilityCheck(): bool
    {
        return $this->value === self::COMPLETE_CLONE;
    }

    public function label(): string
    {
        return self::LABELS[$this->value];
    }

    public function is(string $value): bool
    {
        return $this->value === $value;
    }
}