<?php

namespace App\Services\Retrieve;

use App\Models\Activity;
use App\Models\Setting;
use App\Models\SettingChangeLog;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the full semester roll-forward workflow.
 *
 * Usage from Livewire component:
 *
 *   $service = app(RetrieveService::class);
 *
 *   // 1. (COMPLETE_CLONE only) Check compatibility before proceeding
 *   $report = $service->checkCompatibility($archiveBatchId);
 *
 *   // 2. Apply archived config if user chose "Use Archived Configuration"
 *   if ($resolution === 'use_archived') {
 *       $service->applyArchivedConfig($archiveBatchId);
 *   }
 *
 *   // 3. Execute retrieval
 *   $result = $service->retrieve($archiveBatchId, $mode, $resolution);
 *
 * All database writes happen inside a single DB::transaction().
 * The Livewire component owns the modal state and user flow; this service
 * owns the data layer exclusively.
 */
final class RetrieveService
{
    public function __construct(
        private readonly CompatibilityChecker $compatibilityChecker,
        private readonly ValidationService    $validationService,
    ) {}

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Run the compatibility analysis for a COMPLETE_CLONE retrieval.
     * Safe to call any number of times — read-only, no writes.
     */
    public function checkCompatibility(string $archiveBatchId): CompatibilityReport
    {
        return $this->compatibilityChecker->check($archiveBatchId);
    }

    /**
     * Overwrite the current semester's schedule configuration with the
     * values stored in the archive.  Called only when the user explicitly
     * picks "Use Archived Configuration" in the compatibility resolution step.
     *
     * The config is updated OUTSIDE the retrieval transaction (settings have
     * their own persistence model) so the transaction sees the new config.
     */
    public function applyArchivedConfig(string $archiveBatchId): void
    {
        $row = DB::table('schedule_archives')
            ->where('archive_batch_id', $archiveBatchId)
            ->first();

        if (! $row) {
            return;
        }

        $userId = auth()->id();

        if (! blank($row->start_time ?? null)) {
            Setting::setValue('start_time', $row->start_time, $userId);
        }

        if (! blank($row->end_time ?? null)) {
            Setting::setValue('end_time', $row->end_time, $userId);
        }

        if (! blank($row->active_days ?? null)) {
            $days = is_string($row->active_days) ? json_decode($row->active_days, true) : $row->active_days;
            Setting::setValue('active_days', json_encode($days), $userId);
        }
    }

    /**
     * Execute the full retrieval inside a transaction.
     *
     * @param  string  $archiveBatchId
     * @param  string  $modeValue           One of RetrieveMode::ALL
     * @param  string  $compatResolution    'use_archived' | 'keep_current'
     */
    public function retrieve(
        string $archiveBatchId,
        string $modeValue,
        string $compatResolution = 'keep_current',
    ): RetrieveResult {
        $mode = new RetrieveMode($modeValue);

        return DB::transaction(function () use ($archiveBatchId, $mode, $compatResolution) {
            // ── Validate archive exists ──────────────────────────────────────
            $archive = DB::table('schedule_archives')
                ->where('archive_batch_id', $archiveBatchId)
                ->lockForUpdate()
                ->first();

            if (! $archive) {
                throw new \RuntimeException('The selected archive batch was not found.');
            }

            $period = Setting::getAcademicPeriod();

            // ── Layer 1: Subjects ────────────────────────────────────────────
            $subjectRetriever  = new SubjectRetriever($mode);
            $subjectData       = $subjectRetriever->retrieve($archiveBatchId, $period);

            // ── Layer 2: Schedules (only when mode requires them) ────────────
            $schedCreated  = 0;
            $schedUpdated  = 0;
            $schedSkipped  = 0;
            $needsReview   = 0;
            $schedWarnings = [];

            if ($mode->includesSchedules()) {
                $scheduleRetriever = new ScheduleRetriever($mode, $this->validationService);
                $schedData = $scheduleRetriever->retrieve(
                    $archiveBatchId,
                    $period,
                    $subjectData['bySourceId'],
                    $subjectData['preExistingIds'],
                    $compatResolution,
                );

                $schedCreated  = $schedData['created'];
                $schedUpdated  = $schedData['updated'];
                $schedSkipped  = $schedData['skipped'];
                $needsReview   = $schedData['needsReview'];
                $schedWarnings = $schedData['warnings'];
            }

            // ── Merge warnings ───────────────────────────────────────────────
            $allWarnings = array_merge($subjectData['warnings'], $schedWarnings);

            // ── Audit log ────────────────────────────────────────────────────
            $this->writeAuditLog($archive, $period, $mode, $subjectData, $schedCreated, $schedUpdated, $schedSkipped, $needsReview);

            // ── Build result ─────────────────────────────────────────────────
            return new RetrieveResult(
                subjectsCreated:  $subjectData['created'],
                subjectsUpdated:  $subjectData['updated'],
                facultyAssigned:  $subjectData['facultyAssigned'],
                roomsAssigned:    $subjectData['roomsAssigned'],
                schedulesCreated: $schedCreated,
                schedulesUpdated: $schedUpdated,
                schedulesSkipped: $schedSkipped,
                needsReview:      $needsReview,
                warnings:         $allWarnings,
                archiveBatchId:   $archiveBatchId,
                archiveName:      $archive->semester_name ?? '',
                targetPeriodName: $period['semester_name'],
                mode:             $mode->value,
            );
        });
    }

    // ── Audit log ─────────────────────────────────────────────────────────────

    private function writeAuditLog(
        object $archive,
        array  $period,
        RetrieveMode $mode,
        array  $subjectData,
        int    $schedCreated,
        int    $schedUpdated,
        int    $schedSkipped,
        int    $needsReview,
    ): void {
        $created = $subjectData['created'];
        $updated = $subjectData['updated'];

        SettingChangeLog::create([
            'user_id'       => auth()->id(),
            'setting_key'   => 'semester_retrieval',
            'old_value'     => $archive->archive_batch_id,
            'new_value'     => $period['semester_name'],
            'action'        => 'created',
            'change_reason' => sprintf(
                'Retrieved [%s]: subjects created=%d updated=%d | schedules created=%d updated=%d skipped=%d needsReview=%d',
                $mode->label(),
                $created,
                $updated,
                $schedCreated,
                $schedUpdated,
                $schedSkipped,
                $needsReview,
            ),
            'changed_at'    => now(),
        ]);

        Activity::create([
            'user_id'     => auth()->id(),
            'action'      => 'Retrieve',
            'module'      => 'Semester',
            'description' => "Retrieved {$archive->semester_name} → {$period['semester_name']} [{$mode->label()}].",
        ]);
    }
}