<?php

namespace App\Services\Retrieve;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Retrieves and upserts schedule records from an archived batch.
 *
 * Only invoked when the RetrieveMode includes schedules
 * (KEEP_FACULTY_ROOM or COMPLETE_CLONE).
 *
 * Key behaviours:
 *  • KEEP_FACULTY_ROOM  — copies faculty + room onto a fresh schedule record
 *    but leaves day / start_time / end_time null (blank slate for auto-scheduler).
 *  • COMPLETE_CLONE     — copies everything including timeslot.
 *    Schedules that fail validation are imported with status = 'needs_review'
 *    rather than being silently skipped or modified.
 */
final class ScheduleRetriever
{
    public function __construct(
        private readonly RetrieveMode  $mode,
        private readonly ValidationService $validator,
    ) {}

    /**
     * @param  Collection<int, \App\Models\Subject>  $subjectsBySourceId  From SubjectRetriever
     * @param  Collection<int>                       $preExistingIds      Subject IDs that pre-existed
     * @return array{created: int, updated: int, skipped: int, needsReview: int, warnings: array}
     */
    public function retrieve(
        string     $archiveBatchId,
        array      $period,
        Collection $subjectsBySourceId,
        Collection $preExistingIds,
        string     $compatibilityResolution = 'keep_current',   // 'use_archived' | 'keep_current'
    ): array {
        $sourceSchedules = Schedule::archived()
            ->where('archive_batch', $archiveBatchId)
            ->orderBy('subject_id')
            ->orderBy('day')
            ->orderBy('start_time')
            ->lockForUpdate()
            ->get();

        $created     = 0;
        $updated     = 0;
        $skipped     = 0;
        $needsReview = 0;
        $warnings    = [];
        $pairingMap  = [];

        // Pre-load valid faculty & room IDs for quick existence checks
        $validFacultyIds = Faculty::where('status', 'approved')->pluck('id')->flip();
        $validRoomIds    = Room::pluck('id')->flip();

        foreach ($sourceSchedules as $sourceSchedule) {
            $targetSubject = $subjectsBySourceId->get($sourceSchedule->subject_id);

            if (! $targetSubject) {
                $skipped++;
                continue;
            }

            // Determine schedule status and time data based on mode
            [$timeslot, $targetStatus, $reviewReasons] = $this->resolveTimeslot(
                $sourceSchedule,
                $period,
                $compatibilityResolution,
                $validFacultyIds,
                $validRoomIds,
            );

            if ($reviewReasons !== []) {
                $needsReview++;
                $warnings[] = "Schedule #{$sourceSchedule->id} flagged for review: " . implode('; ', $reviewReasons);
            }

            $isPreExisting = $preExistingIds->contains($targetSubject->id);

            if ($isPreExisting) {
                $result = $this->upsertForExistingSubject(
                    $sourceSchedule,
                    $targetSubject,
                    $period,
                    $timeslot,
                    $targetStatus,
                    $pairingMap,
                );

                $result === 'created' ? $created++ : $updated++;
            } else {
                $this->createForNewSubject(
                    $sourceSchedule,
                    $targetSubject,
                    $period,
                    $timeslot,
                    $targetStatus,
                    $pairingMap,
                );

                $created++;
            }
        }

        return compact('created', 'updated', 'skipped', 'needsReview', 'warnings');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Decide whether to copy the timeslot from the source schedule and
     * what status to assign.
     *
     * Returns [$timeslot, $status, $reviewReasons]
     * where $timeslot is ['day' => ..., 'start_time' => ..., 'end_time' => ...]
     * or null-padded when not copying time.
     */
    private function resolveTimeslot(
        object     $sourceSchedule,
        array      $period,
        string     $resolution,
        Collection $validFacultyIds,
        Collection $validRoomIds,
    ): array {
        if (! $this->mode->includesTimeslots()) {
            // KEEP_FACULTY / KEEP_FACULTY_ROOM: blank timeslot
            // Status = faculty_locked when faculty carried over (shows as PRE-ASSIGNED in UI)
            // Status = partial  when mode carries no faculty at all (shouldn't happen here,
            //          but guard for safety)
            $status = $this->mode->includesFaculty()
                ? Schedule::STATUS_FACULTY_LOCKED
                : Schedule::STATUS_PARTIAL;

            return [
                ['day' => null, 'start_time' => null, 'end_time' => null],
                $status,
                [],
            ];
        }

        // COMPLETE_CLONE path
        $reviewReasons = $this->validator->validateScheduleRecord(
            $sourceSchedule,
            $period,
            $validFacultyIds,
            $validRoomIds,
        );

        if ($reviewReasons !== [] && $resolution === 'keep_current') {
            // Import with time data intact but flag it for manual review
            $timeslot = [
                'day'        => $sourceSchedule->day,
                'start_time' => $sourceSchedule->start_time
                    ? Carbon::parse($sourceSchedule->start_time)->format('H:i:s')
                    : null,
                'end_time' => $sourceSchedule->end_time
                    ? Carbon::parse($sourceSchedule->end_time)->format('H:i:s')
                    : null,
            ];

            return [$timeslot, Schedule::STATUS_NEEDS_REVIEW, $reviewReasons];
        }

        // Compatible or user chose "Use Archived Configuration"
        $timeslot = [
            'day'        => $sourceSchedule->day,
            'start_time' => $sourceSchedule->start_time
                ? Carbon::parse($sourceSchedule->start_time)->format('H:i:s')
                : null,
            'end_time' => $sourceSchedule->end_time
                ? Carbon::parse($sourceSchedule->end_time)->format('H:i:s')
                : null,
        ];

        $targetStatus = $reviewReasons === []
            ? ($sourceSchedule->status ?: Schedule::STATUS_PARTIAL)
            : Schedule::STATUS_NEEDS_REVIEW;

        return [$timeslot, $targetStatus, $reviewReasons];
    }

    /** Handle schedule upsert when the target subject already existed. */
    private function upsertForExistingSubject(
        object  $sourceSchedule,
        object  $targetSubject,
        array   $period,
        array   $timeslot,
        string  $targetStatus,
        array   &$pairingMap,
    ): string {
        $existingSchedules = Schedule::activeTerm($period['semester'], $period['school_year'])
            ->where('subject_id', $targetSubject->id)
            ->get();

        if ($existingSchedules->isEmpty()) {
            $this->createSchedule(
                $sourceSchedule,
                $targetSubject,
                $period,
                $timeslot,
                $targetStatus,
                $pairingMap,
            );

            return 'created';
        }

        // Update the first matching record; leave additional meeting records intact
        $patch = [
            'faculty_id' => $this->mode->includesFaculty() ? $sourceSchedule->faculty_id : $existingSchedules->first()->faculty_id,
            'room_id'    => $this->mode->includesRooms() ? $sourceSchedule->room_id : null,
            'status'     => $targetStatus,
            'edp_code'   => $targetSubject->edp_code,
        ];

        if ($this->mode->includesTimeslots()) {
            $patch['day']        = $timeslot['day'];
            $patch['start_time'] = $timeslot['start_time'];
            $patch['end_time']   = $timeslot['end_time'];
        }

        $existingSchedules->first()->update($patch);

        return 'updated';
    }

    /** Create a brand-new schedule record for a newly created subject. */
    private function createForNewSubject(
        object  $sourceSchedule,
        object  $targetSubject,
        array   $period,
        array   $timeslot,
        string  $targetStatus,
        array   &$pairingMap,
    ): void {
        $this->createSchedule(
            $sourceSchedule,
            $targetSubject,
            $period,
            $timeslot,
            $targetStatus,
            $pairingMap,
        );
    }

    private function createSchedule(
        object  $sourceSchedule,
        object  $targetSubject,
        array   $period,
        array   $timeslot,
        string  $targetStatus,
        array   &$pairingMap,
    ): void {
        Schedule::create([
            'subject_id'        => $targetSubject->id,
            'room_id'           => $this->mode->includesRooms() ? $sourceSchedule->room_id : null,
            'faculty_id'        => $this->mode->includesFaculty() ? $sourceSchedule->faculty_id : null,
            'user_id'           => auth()->id(),
            'department'        => $targetSubject->department,
            'major'             => $targetSubject->major,
            'year_level'        => $targetSubject->year_level,
            'section'           => $targetSubject->section,
            'day'               => $timeslot['day'],
            'start_time'        => $timeslot['start_time'],
            'end_time'          => $timeslot['end_time'],
            'duration_hours'    => $sourceSchedule->duration_hours,
            'meetings_per_week' => $sourceSchedule->meetings_per_week,
            'pairing_key'       => $this->resolvePairingKey($sourceSchedule->pairing_key, $pairingMap),
            'status'            => $targetStatus,
            'edp_code'          => $targetSubject->edp_code,
            'semester'          => $period['semester'],
            'school_year'       => $period['school_year'],
            'academic_year'     => $period['school_year'],
            'workspace_key'     => $period['workspace_key'],
            'is_archived'       => false,
            'archived_at'       => null,
            'archive_batch'     => null,
        ]);
    }

    /** Generate a stable, session-scoped pairing key mapping. */
    private function resolvePairingKey(?string $sourceKey, array &$map): ?string
    {
        if (blank($sourceKey)) {
            return null;
        }

        return $map[$sourceKey] ??= 'retrieved-' . Str::uuid();
    }
}