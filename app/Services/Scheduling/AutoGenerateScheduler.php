<?php

namespace App\Services\Scheduling;

use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\AutoScheduleService;
use Illuminate\Support\Collection;

/**
 * AutoGenerateScheduler
 *
 * Extends AutoScheduleService with cross-section room-mirroring logic.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ROOT CAUSE FIXED HERE
 * ─────────────────────────────────────────────────────────────────────────────
 * When auto-generating Section B schedules, the base scheduler has no awareness
 * that Section A already claimed certain lab rooms (e.g. Room 304 ICT Workshop,
 * Room 305 LAB 2, Room 306 LAB 1) during the morning session. Without a hint,
 * the room-scoring logic may fall back to lecture rooms for Section B lab
 * subjects, because all lab-morning slots look "full" and the scorer doesn't
 * know those same rooms are available in the afternoon.
 *
 * FIX: Before each room-selection attempt, this class looks up what room
 * Section A used for the same subject code within the same
 * department / major / year-level grouping. If found, it temporarily sets
 * `preferred_room_id` on the subject model (without saving to the DB). The
 * parent's `compatibleRooms()` method already honours `preferred_room_id`
 * by pinning that room to position 0 regardless of its raw score. This
 * ensures Room 304's afternoon slots are tried first for Section B subjects
 * that Section A already placed there in the morning.
 *
 * The injection is:
 *   • Non-destructive  — only applied to Section B subjects.
 *   • Non-overriding   — skipped when a ManageRooms pre-assignment already
 *                        exists (`preferred_room_id` already filled).
 *   • In-memory first  — checks the live `$existingSchedules` collection
 *                        before hitting the DB, so batch generation stays fast.
 *   • DB-backed        — falls back to a scoped query for finalized Section A
 *                        schedules that were saved in a previous run.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class AutoGenerateScheduler extends AutoScheduleService
{
    // =========================================================================
    // WAVE 2 HOOK — unassigned subjects (no pre-assigned faculty)
    // Called by processUnassignedSubject → generateLinkedMeetingPattern
    //                                   → findConsistentRoomAndTime  ← HERE
    // =========================================================================

    /**
     * {@inheritdoc}
     *
     * Injects a cross-section room hint before delegating to the parent so the
     * compatible-rooms scorer automatically pins the Section A room for the
     * matching Section B subject.
     */
    public function findConsistentRoomAndTime(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        ?int $meetingsNeeded = null,
        int $meetingIndex = 0,
        bool $allowSessionFallback = true
    ): ?array {
        $this->injectMirrorRoomPreference($subject, $existingSchedules);

        return parent::findConsistentRoomAndTime(
            $subject,
            $rooms,
            $existingSchedules,
            $bounds,
            $meetingsNeeded,
            $meetingIndex,
            $allowSessionFallback
        );
    }

    // =========================================================================
    // WAVE 1 HOOK — pre-assigned faculty subjects
    // Called by processPreAssignedSubject → generateLinkedMeetingPatternForFaculty
    //                                     → findConsistentRoomAndTimeForFaculty
    // (that inner method is private, so we hook via its public wrapper)
    // =========================================================================

    /**
     * {@inheritdoc}
     *
     * Same cross-section room hint injection as Wave 2, applied before the
     * faculty-constrained scheduling path so pre-assigned subjects also land
     * in the correct lab rooms during afternoon sessions.
     */
    public function generateLinkedMeetingPatternForFaculty(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        int $preAssignedFacultyId,
        ?array $bounds = null,
        ?int $meetingsNeeded = null
    ): ?array {
        $this->injectMirrorRoomPreference($subject, $existingSchedules);

        return parent::generateLinkedMeetingPatternForFaculty(
            $subject,
            $rooms,
            $existingSchedules,
            $preAssignedFacultyId,
            $bounds,
            $meetingsNeeded
        );
    }

    // =========================================================================
    // CORE MIRROR LOGIC
    // =========================================================================

    /**
     * For Section B subjects, temporarily set `preferred_room_id` to the room
     * that Section A used for the same subject code — without touching the DB.
     *
     * This is the only place the mirroring logic lives. Both Wave 1 and Wave 2
     * hooks above call this method before delegating to the parent.
     *
     * @param Subject    $subject            The subject being scheduled (mutated in-place).
     * @param Collection $existingSchedules  In-memory schedules already built this run.
     */
    protected function injectMirrorRoomPreference(
        Subject $subject,
        Collection $existingSchedules
    ): void {
        // ── Guard 1: Only applies to Section B ───────────────────────────────
        if (strtoupper((string) ($subject->section ?? '')) !== 'B') {
            return;
        }

        // ── Guard 2: Respect explicit ManageRooms pre-assignments ────────────
        // If an admin already pinned a specific room via ManageRooms, never
        // overwrite it with our inferred mirror room.
        if (filled($subject->preferred_room_id ?? null)) {
            return;
        }

        // ── Step 1: Search the in-memory collection first (fast path) ────────
        // During batch generation, Section A schedules are added to
        // $existingSchedules before Section B is processed. This avoids a
        // DB round-trip for every Section B subject.
        $mirrorRoomId = $existingSchedules
            ->filter(fn ($s) =>
                filled($s->room_id ?? null)
                && filled($s->day ?? null)
                && strtoupper((string) ($s->section ?? '')) === 'A'
                && (string) ($s->department ?? '') === (string) $subject->department
                && (string) ($s->major ?? '')      === (string) $subject->major
                && (int)    ($s->year_level ?? 0)  === (int)    $subject->year_level
            )
            ->first(function ($s) use ($subject) {
                // Match on the eagerly loaded subject relation if available;
                // otherwise try the subject_code stored directly on the
                // schedule row (some code paths store it there).
                $code = $s->subject?->subject_code
                    ?? $s->subject_code
                    ?? null;

                return $code !== null && $code === $subject->subject_code;
            })
            ?->room_id;

        // ── Step 2: Fall back to a DB query for already-saved Section A rows ─
        // Covers the scenario where Section A was generated and saved in a
        // previous run, so its schedules are not in the in-memory collection.
        if (!$mirrorRoomId) {
            $period = Setting::getAcademicPeriod();

            $mirrorRoomId = Schedule::activeTerm($period['semester'], $period['school_year'])
                ->where('section', 'A')
                ->where('department', $subject->department)
                ->where('major',      $subject->major)
                ->where('year_level', (int) $subject->year_level)
                ->whereNotNull('room_id')
                ->whereNotNull('day')
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->whereHas('subject', fn ($q) =>
                    $q->where('subject_code', $subject->subject_code)
                )
                ->value('room_id');
        }

        // ── Step 3: Pin the mirror room ──────────────────────────────────────
        // Setting preferred_room_id on the Eloquent model instance (not calling
        // save()) is enough: the parent's compatibleRooms() reads this attribute
        // and sorts the pinned room to position 0 before any other candidate.
        // The model is never saved, so the DB column remains untouched.
        if ($mirrorRoomId) {
            $subject->preferred_room_id = (int) $mirrorRoomId;
        }
    }
}