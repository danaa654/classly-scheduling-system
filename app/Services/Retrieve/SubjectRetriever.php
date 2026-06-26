<?php

namespace App\Services\Retrieve;

use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Retrieves and upserts subject records from an archived batch into the
 * current active semester workspace.
 *
 * Subjects are the master records; every other retriever depends on the
 * Collection<int, Subject> this class returns (keyed by archived source ID).
 *
 * Three upsert cases:
 *   A – Subject already copied from this source  → update selectively
 *   B – Same offering exists but not flagged     → back-fill copied_from_id, update
 *   C – Genuinely new                            → create
 *
 * Returns a tuple: [Collection $subjectsBySourceId, Collection $preExistingIds, array $counts]
 */
final class SubjectRetriever
{
    public function __construct(private readonly RetrieveMode $mode) {}

    /**
     * Process all archived subjects for the given batch.
     *
     * @return array{
     *     bySourceId: Collection<int, Subject>,
     *     preExistingIds: Collection<int>,
     *     created: int,
     *     updated: int,
     *     facultyAssigned: int,
     *     roomsAssigned: int,
     *     warnings: array,
     * }
     */
    public function retrieve(string $archiveBatchId, array $period): array
    {
        $sourceSubjects = Subject::archived()
            ->where('archive_batch', $archiveBatchId)
            ->orderBy('major')
            ->orderBy('year_level')
            ->orderBy('edp_code')
            ->lockForUpdate()
            ->get();

        if ($sourceSubjects->isEmpty()) {
            throw new \RuntimeException('This archive has no subjects available for retrieval.');
        }

        // Build a fallback map: archivedSubjectId → first room_id seen on its schedules.
        // Used when the subject's own preferred_room_id was never set but the
        // auto-scheduler had directly assigned a room to a schedule record.
        $scheduleRoomFallback = $this->mode->includesRooms()
            ? Schedule::archived()
                ->where('archive_batch', $archiveBatchId)
                ->whereNotNull('room_id')
                ->pluck('room_id', 'subject_id')
            : collect();

        /** @var Collection<int, Subject> $bySourceId  Maps archived subject.id → active Subject */
        $bySourceId       = collect();
        /** @var Collection<int> $preExistingIds  IDs of subjects that already existed */
        $preExistingIds   = collect();

        $created         = 0;
        $updated         = 0;
        $facultyAssigned = 0;
        $roomsAssigned   = 0;
        $warnings        = [];

        foreach ($sourceSubjects as $source) {
            [$subject, $wasNew] = $this->upsertSubject(
                $source,
                $period,
                $scheduleRoomFallback,
                $warnings,
            );

            $bySourceId->put($source->id, $subject);

            if ($wasNew) {
                $created++;
            } else {
                $updated++;
                $preExistingIds->push($subject->id);
            }

            if ($this->mode->includesFaculty() && $subject->faculty_id) {
                $facultyAssigned++;
            }

            if ($this->mode->includesRooms() && $subject->preferred_room_id) {
                $roomsAssigned++;
            }
        }

        return compact('bySourceId', 'preExistingIds', 'created', 'updated', 'facultyAssigned', 'roomsAssigned', 'warnings');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Upsert one subject record.
     *
     * @return array{0: Subject, 1: bool}  [subject, wasNew]
     */
    private function upsertSubject(
        object     $source,
        array      $period,
        Collection $scheduleRoomFallback,
        array      &$warnings,
    ): array {
        $facultyId = $this->mode->includesFaculty()  ? $source->faculty_id        : null;
        $roomId    = $this->mode->includesRooms()
            ? ($source->preferred_room_id ?? $scheduleRoomFallback->get($source->id))
            : null;

        // ── Case A: already copied from this source in a prior run ───────────
        $existing = Subject::activeTerm($period['semester'], $period['school_year'])
            ->where('copied_from_id', $source->id)
            ->first();

        if ($existing) {
            $patch = array_filter([
                'faculty_id'        => $facultyId,
                'preferred_room_id' => $roomId,
            ], fn ($v) => $v !== null);

            if ($patch !== []) {
                $existing->update($patch);
            }

            return [$existing->fresh(), false];
        }

        // ── Case B: same offering exists but wasn't flagged ──────────────────
        $existing = Subject::activeTerm($period['semester'], $period['school_year'])
            ->where('subject_code', $source->subject_code)
            ->where('section',      $source->section)
            ->where('major',        $source->major)
            ->where('year_level',   $source->year_level)
            ->where('department',   $source->department)
            ->first();

        if ($existing) {
            $patch = array_filter([
                'faculty_id'        => $facultyId,
                'preferred_room_id' => $roomId,
                'copied_from_id'    => $source->id,   // back-fill
            ], fn ($v) => $v !== null);

            if ($patch !== []) {
                $existing->update($patch);
            }

            return [$existing->fresh(), false];
        }

        // ── Case C: brand-new subject ────────────────────────────────────────
        $newSubject = Subject::create([
            'edp_code'           => Subject::generateEdpCode(
                $source->major ?: strtok((string) $source->edp_code, '-'),
                (int) $source->year_level,
                $period['school_year'],
                $period['semester'],
            ),
            'subject_code'        => $source->subject_code,
            'section'             => $source->section,
            'description'         => $source->description,
            'major'               => $source->major,
            'year_level'          => $source->year_level,
            'department'          => $source->department,
            'units'               => $source->units,
            'duration_hours'      => $source->duration_hours,
            'type'                => $source->type,
            'subject_type'        => $source->subject_type,
            'requires_lab'        => (bool) ($source->requires_lab ?? false),
            'preferred_room_type' => $source->preferred_room_type,
            'specialization'      => $source->specialization,
            'meetings_per_week'   => $source->meetings_per_week,
            // ── Mode-gated fields ─────────────────────────────────────────
            'faculty_id'          => $facultyId,
            'preferred_room_id'   => $roomId,
            // ── Allow-faculty-override flags (preserved from source) ──────
            'allow_department_faculty'       => (bool) ($source->allow_department_faculty       ?? false),
            'allow_gened_faculty'            => (bool) ($source->allow_gened_faculty            ?? false),
            'allow_cross_department_faculty' => (bool) ($source->allow_cross_department_faculty ?? false),
            // ── Practicum flag ────────────────────────────────────────────
            'is_practicum'        => (bool) ($source->is_practicum ?? false),
            // ── Workspace / lifecycle ─────────────────────────────────────
            'semester'            => $period['semester'],
            'school_year'         => $period['school_year'],
            'academic_year'       => $period['school_year'],
            'workspace_key'       => $period['workspace_key'],
            'is_archived'         => false,
            'archived_at'         => null,
            'copied_from_id'      => $source->id,
            'archive_batch'       => null,
        ]);

        return [$newSubject, true];
    }
}