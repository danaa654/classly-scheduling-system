<?php

namespace App\Services\Retrieve;

/**
 * Immutable summary returned by RetrieveService after a successful retrieval.
 *
 * Every counter maps 1-to-1 with the "Retrieval Completed" summary shown
 * to the user, so the Livewire component just passes this object to the view.
 */
final class RetrieveResult
{
    public function __construct(
        // ── Subject layer ────────────────────────────────────────────────────
        public readonly int    $subjectsCreated  = 0,
        public readonly int    $subjectsUpdated  = 0,

        // ── Faculty layer ────────────────────────────────────────────────────
        public readonly int    $facultyAssigned  = 0,

        // ── Room layer ───────────────────────────────────────────────────────
        public readonly int    $roomsAssigned    = 0,

        // ── Schedule layer ───────────────────────────────────────────────────
        public readonly int    $schedulesCreated = 0,
        public readonly int    $schedulesUpdated = 0,
        public readonly int    $schedulesSkipped = 0,

        // ── Validation warnings ──────────────────────────────────────────────
        /** Schedules imported but flagged "needs_review" with a reason. */
        public readonly int    $needsReview      = 0,
        /** Free-text warning messages (e.g. "Faculty X no longer exists"). */
        public readonly array  $warnings         = [],

        // ── Context ──────────────────────────────────────────────────────────
        public readonly string $archiveBatchId   = '',
        public readonly string $archiveName      = '',
        public readonly string $targetPeriodName = '',
        public readonly string $mode             = '',
    ) {}

    public function totalSubjects(): int
    {
        return $this->subjectsCreated + $this->subjectsUpdated;
    }

    public function warningCount(): int
    {
        return count($this->warnings) + $this->needsReview;
    }

    /** Human-readable one-line subject summary for the toast notification. */
    public function subjectSummary(): string
    {
        $parts = [];

        if ($this->subjectsCreated > 0) {
            $parts[] = "{$this->subjectsCreated} created";
        }
        if ($this->subjectsUpdated > 0) {
            $parts[] = "{$this->subjectsUpdated} updated";
        }

        return $parts
            ? implode(', ', $parts) . ' subject' . ($this->totalSubjects() === 1 ? '' : 's')
            : 'no subjects changed';
    }

    /** Human-readable one-line schedule summary for the toast notification. */
    public function scheduleSummary(): string
    {
        $total = $this->schedulesCreated + $this->schedulesUpdated;

        if ($total === 0) {
            return 'no schedules changed';
        }

        $parts = [];

        if ($this->schedulesCreated > 0) {
            $parts[] = "{$this->schedulesCreated} created";
        }
        if ($this->schedulesUpdated > 0) {
            $parts[] = "{$this->schedulesUpdated} updated";
        }
        if ($this->schedulesSkipped > 0) {
            $parts[] = "{$this->schedulesSkipped} skipped";
        }
        if ($this->needsReview > 0) {
            $parts[] = "{$this->needsReview} need review";
        }

        return implode(', ', $parts);
    }

    /** Array form suitable for compact() / json_encode() / logging. */
    public function toArray(): array
    {
        return [
            'subjects_created'  => $this->subjectsCreated,
            'subjects_updated'  => $this->subjectsUpdated,
            'faculty_assigned'  => $this->facultyAssigned,
            'rooms_assigned'    => $this->roomsAssigned,
            'schedules_created' => $this->schedulesCreated,
            'schedules_updated' => $this->schedulesUpdated,
            'schedules_skipped' => $this->schedulesSkipped,
            'needs_review'      => $this->needsReview,
            'warnings'          => $this->warnings,
            'archive_batch_id'  => $this->archiveBatchId,
            'archive_name'      => $this->archiveName,
            'target_period'     => $this->targetPeriodName,
            'mode'              => $this->mode,
        ];
    }
}