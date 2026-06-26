<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\SettingChangeLog;
use App\Models\Subject;
use App\Services\Retrieve\RetrieveMode;
use App\Services\Retrieve\RetrieveService;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;
use Throwable;

class GlobalSettings extends Component
{
    public const BRICK_DURATION = 0.5;

    public const LUNCH_START = '12:00';

    public const LUNCH_END = '13:00';

    // -------------------------------------------------------------------------
    // Form-bound settings
    // -------------------------------------------------------------------------
    public $day_start;

    public $day_end;

    public $school_year;

    public $semester;

    public $semester_name;

    public array $active_days = [];

    public bool $config_locked = true;

    public bool $maintenance_mode = false;

    // -------------------------------------------------------------------------
    // System-ready state
    // -------------------------------------------------------------------------

    /** Whether the system has been marked ready for the current semester. */
    public bool $system_ready = false;

    /** Structured checklist items for the setup wizard. */
    public array $setupChecklist = [];

    /** Whether all checklist items are satisfied (computed). */
    public bool $setupComplete = false;

    /** Confirmation modal for marking system ready. */
    public bool $confirmingMarkReady = false;

    /** Confirmation modal for reverting system to not-ready. */
    public bool $confirmingMarkNotReady = false;

    // -------------------------------------------------------------------------
    // Semester end / reset
    // -------------------------------------------------------------------------
    public bool $confirmingReset = false;

    public bool $showSemesterBlockerModal = false;

    public array $semesterEndBlockers = [];

    // -------------------------------------------------------------------------
    // Archive retrieval — wizard state
    // -------------------------------------------------------------------------

    /** Step 1: mode selection modal */
    public bool $showRetrieveModal = false;

    /** Step 2: compatibility report (COMPLETE_CLONE only) */
    public bool $showCompatibilityStep = false;

    /** Step 3: final confirmation */
    public bool $showRetrieveConfirmation = false;

    public ?string $retrieveArchiveBatch = null;

    /** One of RetrieveMode::ALL — default is Subjects Only */
    public string $retrieveMode = RetrieveMode::SUBJECTS_ONLY;

    public ?string $selectedHistoricalSemester = null;

    public string $archiveFilterSemester = '';

    public string $archiveFilterSchoolYear = '';

    public string $archiveFilterDepartment = '';

    public array $changeHistory = [];

    /** Auto-detected archive record shown in the modal header */
    public ?array $matchingArchive = null;

    public array $workspaceOccupancy = [];

    /** CompatibilityReport serialised to array for the blade (COMPLETE_CLONE only) */
    public ?array $compatibilityReport = null;

    /**
     * How the user resolves a compatibility conflict for COMPLETE_CLONE:
     *   'use_archived'  → apply archived config to current semester then clone
     *   'keep_current'  → clone but flag out-of-bounds schedules as needs_review
     *
     * @var string
     */
    public string $compatibilityResolution = 'keep_current';

    /** Whether the final confirmation safeguard checkbox is acknowledged */
    public bool $archiveAcknowledged = false;

    /** Whether a retrieve has already been performed for the current semester term. */
    public bool $alreadyRetrievedCurrentTerm = false;

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['admin', 'registrar'], true)) {
            abort(403, 'Unauthorized access to system configurations.');
        }

        $this->loadAllSettings();

        if (request()->boolean('unlock')) {
            $this->unlockForSetup();
        }

        $this->loadChangeHistory();
    }

    // =========================================================================
    // WATCHERS
    // =========================================================================

    public function updatedSemester(): void
    {
        $this->semester      = Setting::normalizeSemester($this->semester);
        $this->semester_name = Setting::semesterDisplayName($this->semester, $this->school_year);
    }

    public function updatedSchoolYear(): void
    {
        $this->semester_name = Setting::semesterDisplayName($this->semester, $this->school_year);
    }

    // =========================================================================
    // LOAD HELPERS
    // =========================================================================

    private function loadAllSettings(): void
    {
        $scheduleSettings = Setting::getScheduleSettings();
        $period           = Setting::getAcademicPeriod();

        $this->active_days      = $scheduleSettings['active_days'];
        $this->day_start        = $scheduleSettings['start_time'];
        $this->day_end          = $scheduleSettings['end_time'];
        $this->school_year      = $period['school_year'];
        $this->semester         = $period['semester'];
        $this->semester_name    = $period['semester_name'];
        $this->config_locked    = Setting::isConfigLocked();
        $this->maintenance_mode = Setting::getBoolean('maintenance_mode', false);

        // System-ready state
        $this->system_ready   = Setting::isSystemReady();
        $this->setupChecklist = Setting::getSetupChecklist();
        $this->setupComplete  = Setting::isSetupComplete();

        // Retrieval guard
        $this->alreadyRetrievedCurrentTerm = $this->hasRetrievedCurrentTerm();
    }

    private function loadChangeHistory(): void
    {
        $semesterStartedAt = SettingChangeLog::where('setting_key', 'semester_archive')
            ->latest('changed_at')
            ->value('changed_at');

        $query = SettingChangeLog::with('user:id,name')->latest('changed_at')->take(15);

        if ($semesterStartedAt) {
            $query->where('changed_at', '>=', $semesterStartedAt);
        }

        $this->changeHistory = $query->get()->toArray();
    }

    // =========================================================================
    // LOCK / MAINTENANCE TOGGLES
    // =========================================================================

    public function toggleLock(): void
    {
        $this->config_locked = ! $this->config_locked;
        $this->persistSetting('config_locked', $this->config_locked ? '1' : '0', $this->config_locked ? 'locked' : 'unlocked');

        if (! $this->config_locked) {
            Setting::markSystemNotReady(auth()->id());
            $this->system_ready = false;
        }

        $this->setupChecklist = Setting::getSetupChecklist();
        $this->setupComplete  = Setting::isSetupComplete();

        $this->dispatch('notify', [
            'type'    => $this->config_locked ? 'info' : 'warning',
            'message' => $this->config_locked
                ? 'Configuration locked. Changes are disabled.'
                : 'Configuration unlocked. System marked as not ready until you save and lock again.',
        ]);
    }

    private function unlockForSetup(): void
    {
        if (! $this->config_locked) {
            return;
        }

        $this->config_locked = false;
        $this->persistSetting('config_locked', '0', 'unlocked');
        Setting::markSystemNotReady(auth()->id());

        $this->system_ready   = false;
        $this->setupChecklist = Setting::getSetupChecklist();
        $this->setupComplete  = Setting::isSetupComplete();

        $this->dispatch('notify', [
            'type'    => 'warning',
            'message' => 'Configuration unlocked. Save the settings to lock them again, then mark the system as ready.',
        ]);
    }

    public function toggleMaintenanceMode(): void
    {
        $this->maintenance_mode = ! $this->maintenance_mode;
        $this->persistSetting('maintenance_mode', $this->maintenance_mode ? '1' : '0');

        $this->dispatch('notify', [
            'type'    => 'warning',
            'message' => $this->maintenance_mode
                ? 'Maintenance mode is on. Deans cannot modify subjects.'
                : 'Maintenance mode is off.',
        ]);
    }

    // =========================================================================
    // SAVE SETTINGS
    // =========================================================================

    public function save(): void
    {
        if ($this->config_locked) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Configuration is locked. Unlock it before saving changes.',
            ]);

            return;
        }

        $this->validate([
            'school_year'    => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester'       => ['required', Rule::in(Setting::SEMESTERS)],
            'semester_name'  => ['required', 'string', 'min:3'],
            'active_days'    => ['required', 'array', 'min:1'],
            'active_days.*'  => ['required', 'string', Rule::in(Setting::ALL_SCHEDULE_DAYS)],
            'day_start'      => ['required', 'date_format:H:i'],
            'day_end'        => ['required', 'date_format:H:i', 'after:day_start'],
        ]);

        $this->semester      = Setting::normalizeSemester($this->semester);
        $this->active_days   = Setting::normalizeActiveDays($this->active_days);
        $this->semester_name = trim((string) $this->semester_name);

        if (! $this->validateAllTimeIncrements()) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'All times must use 30-minute increments.',
            ]);

            return;
        }

        if (Carbon::parse($this->day_start)->diffInMinutes(Carbon::parse($this->day_end)) < (self::BRICK_DURATION * 60)) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'The school day is too short for one scheduling brick.',
            ]);

            return;
        }

        if ($this->academicPeriodChanged() && ($this->storedActiveSubjectCount() + $this->storedActiveScheduleCount()) > 0) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Use Reset Semester before changing the active academic period.',
            ]);

            return;
        }

        $conflicts = $this->hasScheduleConflicts();
        if ($conflicts !== []) {
            $detail = collect($conflicts)
                ->map(fn (array $conflict) => "{$conflict['subject_code']} on {$conflict['day']} ({$conflict['time']}) {$conflict['issue']}")
                ->implode("\n");

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Existing schedules conflict with the new day bounds.',
                'detail'  => $detail,
            ]);

            return;
        }

        try {
            DB::transaction(function () {
                $this->persistSetting('school_year', $this->school_year);
                $this->persistSetting('semester', $this->semester);
                $this->persistSetting('semester_name', $this->semester_name);
                $this->persistSetting('active_days', $this->active_days);
                $this->persistSetting('day_start', $this->day_start);
                $this->persistSetting('day_end', $this->day_end);
                $this->persistSetting('config_locked', '1', 'locked');
            });

            $this->config_locked = true;

            $this->setupChecklist = Setting::getSetupChecklist();
            $this->setupComplete  = Setting::isSetupComplete();

            $this->loadChangeHistory();
            $this->dispatch('settings-updated')->to(MasterGrid::class);

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => 'System configuration saved and locked.'
                    . ($this->setupComplete && ! $this->system_ready
                        ? ' All checklist items complete — you can now mark the system as ready.'
                        : ''),
            ]);
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Save failed: ' . $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // SYSTEM READY ACTIONS
    // =========================================================================

    public function openMarkReadyConfirmation(): void
    {
        $this->setupChecklist = Setting::getSetupChecklist();
        $this->setupComplete  = Setting::isSetupComplete();

        if (! $this->setupComplete) {
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'Complete all setup checklist items before marking the system as ready.',
            ]);

            return;
        }

        $this->confirmingMarkReady = true;
    }

    public function markSystemReady(): void
    {
        $this->setupChecklist = Setting::getSetupChecklist();
        $this->setupComplete  = Setting::isSetupComplete();

        if (! $this->setupComplete) {
            $this->confirmingMarkReady = false;

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Cannot mark as ready — not all checklist items are satisfied.',
            ]);

            return;
        }

        try {
            Setting::markSystemReady(auth()->id());

            SettingChangeLog::create([
                'user_id'       => auth()->id(),
                'setting_key'   => 'system_ready',
                'old_value'     => '0',
                'new_value'     => '1',
                'action'        => 'updated',
                'change_reason' => 'Admin/Registrar marked the system as ready for the new semester.',
                'changed_at'    => now(),
            ]);

            Activity::create([
                'user_id'     => auth()->id(),
                'action'      => 'System Ready',
                'module'      => 'Settings',
                'description' => 'Marked system as ready for ' . Setting::getAcademicPeriod()['semester_name'],
            ]);

            $this->system_ready        = true;
            $this->confirmingMarkReady = false;
            $this->setupChecklist      = Setting::getSetupChecklist();
            $this->setupComplete       = Setting::isSetupComplete();
            $this->loadChangeHistory();

            $this->dispatch('system-ready-changed', ready: true);

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => 'System is now marked as ready. Other roles will be notified automatically.',
            ]);
        } catch (Throwable $e) {
            $this->confirmingMarkReady = false;

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Failed to mark system ready: ' . $e->getMessage(),
            ]);
        }
    }

    public function openMarkNotReadyConfirmation(): void
    {
        $this->confirmingMarkNotReady = true;
    }

    public function markSystemNotReady(): void
    {
        try {
            Setting::markSystemNotReady(auth()->id());

            SettingChangeLog::create([
                'user_id'       => auth()->id(),
                'setting_key'   => 'system_ready',
                'old_value'     => '1',
                'new_value'     => '0',
                'action'        => 'updated',
                'change_reason' => 'Admin/Registrar reverted the system to not-ready.',
                'changed_at'    => now(),
            ]);

            Activity::create([
                'user_id'     => auth()->id(),
                'action'      => 'System Not Ready',
                'module'      => 'Settings',
                'description' => 'Reverted system ready status for ' . Setting::getAcademicPeriod()['semester_name'],
            ]);

            $this->system_ready           = false;
            $this->confirmingMarkNotReady = false;
            $this->loadChangeHistory();

            $this->dispatch('system-ready-changed', ready: false);

            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'System has been reverted to not-ready. Other roles will see the holding screen.',
            ]);
        } catch (Throwable $e) {
            $this->confirmingMarkNotReady = false;

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Failed to revert system ready state: ' . $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // SEMESTER END / RESET
    // =========================================================================

    public function openEndSemesterConfirmation(): void
    {
        $this->semesterEndBlockers = $this->semesterEndValidationBlockers();

        if ($this->semesterEndBlockers !== []) {
            $this->showSemesterBlockerModal = true;
            $this->confirmingReset          = false;

            return;
        }

        $this->confirmingReset = true;
    }

    public function endSemester(): void
    {
        $this->semesterEndBlockers = $this->semesterEndValidationBlockers();

        if ($this->semesterEndBlockers !== []) {
            $this->showSemesterBlockerModal = true;
            $this->confirmingReset          = false;
            $this->archiveAcknowledged      = false;

            return;
        }

        if (! $this->archiveAcknowledged) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Confirm the archive safeguard before ending the semester.',
            ]);

            return;
        }

        try {
            $result = DB::transaction(function () {
                $archive    = $this->archiveCurrentSemester();
                $nextPeriod = $this->advanceAcademicPeriod();

                if (Schema::hasTable('schedule_archives')) {
                    DB::table('schedule_archives')
                        ->where('archive_batch_id', $archive['archive_batch_id'])
                        ->update([
                            'next_semester'    => $nextPeriod['semester'],
                            'next_school_year' => $nextPeriod['school_year'],
                            'updated_at'       => now(),
                        ]);
                }

                Setting::markSystemNotReady(auth()->id());

                // Clear the retrieval guard so the new semester allows one retrieve.
                $this->clearRetrievedCurrentTerm();

                return compact('archive', 'nextPeriod');
            });

            $this->confirmingReset     = false;
            $this->archiveAcknowledged = false;

            $this->loadAllSettings();
            $this->loadChangeHistory();

            $this->dispatch('settings-updated')->to(MasterGrid::class);
            $this->dispatch('subjectUpdated');
            $this->dispatch('system-ready-changed', ready: false);

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => "Archived {$result['archive']['archive_batch_id']} and advanced to {$result['nextPeriod']['semester_name']}. Redirecting to your dashboard to begin setup.",
            ]);

            $this->dispatch('semester-ended', redirectTo: $this->getDashboardUrl());
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'End semester failed: ' . $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // ARCHIVE / RETRIEVAL — private archive helpers
    // =========================================================================

    private function archiveCurrentSemester(): array
    {
        $this->ensureLifecycleSchema();

        $period     = Setting::getAcademicPeriod();
        $nextPeriod = Setting::nextAcademicPeriod($period['semester'], $period['school_year']);
        $now        = now();

        $subjects = $this->currentWorkspaceSubjectsQuery($period)
            ->lockForUpdate()
            ->get();

        $schedules = $this->currentWorkspaceSchedulesQuery($period)
            ->with(['subject:id,edp_code,subject_code,description,units', 'faculty:id,full_name,employee_id', 'room:id,room_name,type', 'user:id,name'])
            ->lockForUpdate()
            ->get();

        if ($subjects->isEmpty() && $schedules->isEmpty()) {
            throw new RuntimeException('There are no active subjects or schedules to archive.');
        }

        $archiveBatchId = $this->nextArchiveBatchId($period['semester'], $period['school_year']);

        foreach ($subjects as $subject) {
            $subject->forceFill([
                'semester'      => $period['semester'],
                'school_year'   => $period['school_year'],
                'academic_year' => $period['school_year'],
                'workspace_key' => $period['workspace_key'],
                'is_archived'   => true,
                'archived_at'   => $now,
                'archive_batch' => $archiveBatchId,
            ])->save();
        }

        foreach ($schedules as $schedule) {
            $schedule->forceFill([
                'edp_code'      => $schedule->edp_code ?: $schedule->subject?->edp_code,
                'semester'      => $period['semester'],
                'school_year'   => $period['school_year'],
                'academic_year' => $period['school_year'],
                'workspace_key' => $period['workspace_key'],
                'is_archived'   => true,
                'archived_at'   => $now,
                'archive_batch' => $archiveBatchId,
            ])->save();
        }

        if (Schema::hasTable('schedule_archives')) {
            DB::table('schedule_archives')->insert([
                'archive_batch_id' => $archiveBatchId,
                'semester'         => $period['semester'],
                'semester_name'    => $period['semester_name'],
                'school_year'      => $period['school_year'],
                'total_subjects'   => $subjects->count(),
                'total_schedules'  => $schedules->count(),
                'schedule_data'    => $this->buildArchiveSnapshot($subjects, $schedules, $archiveBatchId),
                'metadata'         => json_encode([
                    'previous_period'   => $period,
                    'next_period'       => $nextPeriod,
                    'active_edp_prefix' => $period['edp_prefix'],
                ]),
                'archived_by'   => auth()->id(),
                'archive_notes' => 'Semester lifecycle archive. Records remain in source tables as locked archived rows.',
                'is_locked'     => true,
                'archived_at'   => $now,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        SettingChangeLog::create([
            'user_id'       => auth()->id(),
            'setting_key'   => 'semester_archive',
            'old_value'     => $period['semester_name'],
            'new_value'     => $archiveBatchId,
            'action'        => 'reset',
            'change_reason' => "Archived {$subjects->count()} subjects and {$schedules->count()} schedules.",
            'changed_at'    => $now,
        ]);

        Activity::create([
            'user_id'     => auth()->id(),
            'action'      => 'Archive',
            'module'      => 'Semester',
            'description' => 'Archived ' . $period['semester_name'],
        ]);

        return [
            'archive_batch_id' => $archiveBatchId,
            'semester'         => $period['semester'],
            'school_year'      => $period['school_year'],
            'total_subjects'   => $subjects->count(),
            'total_schedules'  => $schedules->count(),
        ];
    }

    private function advanceAcademicPeriod(): array
    {
        $current = Setting::getAcademicPeriod();
        $next    = Setting::nextAcademicPeriod($current['semester'], $current['school_year']);

        $this->persistSetting('semester', $next['semester']);
        $this->persistSetting('school_year', $next['school_year']);
        $this->persistSetting('semester_name', $next['semester_name']);
        $this->persistSetting('config_locked', '1', 'locked');

        return $next;
    }

    // =========================================================================
    // RETRIEVE WIZARD — Step 1: Open modal
    // =========================================================================

    public function openRetrieveModal(): void
    {
        $this->ensureLifecycleSchema();

        if ($this->hasRetrievedCurrentTerm()) {
            $period = Setting::getAcademicPeriod();
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'Already retrieved for ' . $period['semester_name'] . '. You can only retrieve once per semester. End the semester first to start a new term.',
            ]);

            return;
        }

        $matching = $this->findMatchingSemesterArchive();

        if (! $matching) {
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'No archived ' . $this->semesterLabel() . ' semester found.',
            ]);

            return;
        }

        $this->resetRetrieveState();

        $this->retrieveArchiveBatch = $matching['archive_batch_id'];
        $this->matchingArchive      = $matching;
        $this->workspaceOccupancy   = $this->getWorkspaceOccupancy();
        $this->showRetrieveModal    = true;
    }

    // =========================================================================
    // RETRIEVE WIZARD — Step 2a: Proceed (all modes)
    // =========================================================================

    public function proceedToRetrieveConfirmation(): void
    {
        $this->ensureLifecycleSchema();

        if (! $this->retrieveArchiveBatch) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No archive selected.']);

            return;
        }

        $mode = new RetrieveMode($this->retrieveMode);

        // Close the mode-selection modal before advancing to any next step.
        // Without this, both the mode modal and the next modal render simultaneously
        // (both use z-40) and the compatibility step has nothing to render into.
        $this->showRetrieveModal = false;

        // COMPLETE_CLONE requires a compatibility check before the confirmation step
        if ($mode->requiresCompatibilityCheck()) {
            $this->runCompatibilityCheck();

            return;
        }

        // All other modes go straight to confirmation
        $this->workspaceOccupancy       = $this->getWorkspaceOccupancy();
        $this->showRetrieveConfirmation = true;
    }

    // =========================================================================
    // RETRIEVE WIZARD — Step 2b: Compatibility check (COMPLETE_CLONE only)
    // =========================================================================

    private function runCompatibilityCheck(): void
    {
        /** @var RetrieveService $service */
        $service = app(RetrieveService::class);
        $report  = $service->checkCompatibility($this->retrieveArchiveBatch);

        if ($report->isCompatible()) {
            // No differences — skip the compatibility step entirely
            $this->workspaceOccupancy       = $this->getWorkspaceOccupancy();
            $this->showRetrieveConfirmation = true;

            return;
        }

        $this->compatibilityReport  = $report->toArray();
        $this->showCompatibilityStep = true;
    }

    /**
     * User chose how to resolve the compatibility conflict and clicks "Continue".
     * $resolution: 'use_archived' | 'keep_current' | 'cancel'
     */
    public function resolveCompatibility(string $resolution): void
    {
        if ($resolution === 'cancel') {
            $this->resetRetrieveState();

            return;
        }

        if (! in_array($resolution, ['use_archived', 'keep_current'], true)) {
            return;
        }

        $this->compatibilityResolution  = $resolution;
        $this->showCompatibilityStep    = false;
        $this->workspaceOccupancy       = $this->getWorkspaceOccupancy();
        $this->showRetrieveConfirmation = true;
    }

    // =========================================================================
    // RETRIEVE WIZARD — Step 3: Execute
    // =========================================================================

    public function retrieveArchivedSemester(): void
    {
        $this->ensureLifecycleSchema();

        if (! $this->retrieveArchiveBatch) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Select an archived semester to retrieve.']);

            return;
        }

        /** @var RetrieveService $service */
        $service = app(RetrieveService::class);

        try {
            // Apply archived config BEFORE the retrieval transaction when the
            // user chose "Use Archived Configuration" in the compatibility step.
            if ($this->compatibilityResolution === 'use_archived') {
                $service->applyArchivedConfig($this->retrieveArchiveBatch);
            }

            $result = $service->retrieve(
                $this->retrieveArchiveBatch,
                $this->retrieveMode,
                $this->compatibilityResolution,
            );

            // ── Post-retrieval cleanup ────────────────────────────────────────
            $this->resetRetrieveState();
            $this->markRetrievedCurrentTerm();
            $this->alreadyRetrievedCurrentTerm = true;
            $this->loadChangeHistory();
            $this->dispatch('subjectUpdated');

            // ── Success notifications ─────────────────────────────────────────
            $needsReviewSuffix = $result->needsReview > 0
                ? " — {$result->needsReview} schedule(s) flagged for review."
                : '.';

            $warningSuffix = count($result->warnings) > 0
                ? ' Check Faculty Loading and Block Schedule for details.'
                : '';

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => "Retrieved {$result->targetPeriodName}: {$result->subjectSummary()}.",
                'detail'  => ucfirst($result->scheduleSummary()) . $needsReviewSuffix . $warningSuffix,
            ]);

            // Broadcast to other Livewire components on the same page
            $this->dispatch('semesterRetrieved', result: $result->toArray());

        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Retrieval failed: ' . $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // ARCHIVE QUERY HELPERS
    // =========================================================================

    public function findMatchingSemesterArchive(): ?array
    {
        if (! Schema::hasTable('schedule_archives')) {
            return null;
        }

        $period          = Setting::getAcademicPeriod();
        $currentSemester = $period['semester'];

        $matchingArchives = DB::table('schedule_archives')
            ->where('semester', $currentSemester)
            ->where('total_subjects', '>', 0)
            ->whereNotNull('archive_batch_id')
            ->select(
                'archive_batch_id',
                'semester',
                'semester_name',
                'school_year',
                'total_subjects',
                'total_schedules',
                'archived_at'
            )
            ->orderByDesc('school_year')
            ->get();

        if ($matchingArchives->isEmpty()) {
            return null;
        }

        $latest = $matchingArchives->first();

        return [
            'archive_batch_id' => $latest->archive_batch_id,
            'semester'         => $latest->semester,
            'semester_name'    => $latest->semester_name ?: Setting::semesterDisplayName($latest->semester, $latest->school_year),
            'school_year'      => $latest->school_year,
            'total_subjects'   => $latest->total_subjects,
            'total_schedules'  => $latest->total_schedules,
            'archived_at'      => $latest->archived_at,
        ];
    }

    public function getWorkspaceOccupancy(): array
    {
        $period = Setting::getAcademicPeriod();

        $subjectCount  = Subject::activeTerm($period['semester'], $period['school_year'])->count();
        $scheduleCount = Schedule::activeTerm($period['semester'], $period['school_year'])->count();

        return [
            'has_subjects'   => $subjectCount > 0,
            'has_schedules'  => $scheduleCount > 0,
            'subject_count'  => $subjectCount,
            'schedule_count' => $scheduleCount,
            'is_occupied'    => $subjectCount > 0 || $scheduleCount > 0,
        ];
    }

    public function archivedSemesterOptions(): Collection
    {
        if (! Schema::hasTable('schedule_archives')) {
            return collect();
        }

        return DB::table('schedule_archives')
            ->select('id', 'archive_batch_id', 'semester', 'semester_name', 'school_year', 'total_subjects', 'total_schedules', 'archived_at')
            ->latest('archived_at')
            ->get()
            ->map(fn ($archive) => (object) [
                'value'       => $archive->archive_batch_id ? 'batch:' . $archive->archive_batch_id : 'legacy:' . $archive->id,
                'batch'       => $archive->archive_batch_id,
                'label'       => ($archive->semester_name ?: Setting::semesterDisplayName($archive->semester, $archive->school_year)),
                'school_year' => $archive->school_year,
                'badge'       => "{$archive->total_subjects} subjects / {$archive->total_schedules} schedules",
                'date'        => $archive->archived_at,
            ]);
    }

    public function retrievableArchiveOptions(): Collection
    {
        if (! Schema::hasTable('schedule_archives')) {
            return collect();
        }

        return DB::table('schedule_archives')
            ->whereNotNull('archive_batch_id')
            ->where('total_subjects', '>', 0)
            ->select('archive_batch_id', 'semester', 'semester_name', 'school_year', 'total_subjects', 'archived_at')
            ->latest('archived_at')
            ->get();
    }

    public function archiveHistoryBatches(): Collection
    {
        if (! Schema::hasTable('schedule_archives')) {
            return collect();
        }

        return DB::table('schedule_archives')
            ->leftJoin('users', 'users.id', '=', 'schedule_archives.archived_by')
            ->when($this->archiveFilterSemester !== '', fn ($query) => $query->where('schedule_archives.semester', Setting::normalizeSemester($this->archiveFilterSemester)))
            ->when($this->archiveFilterSchoolYear !== '', fn ($query) => $query->where('schedule_archives.school_year', $this->archiveFilterSchoolYear))
            ->select(
                'schedule_archives.id',
                'schedule_archives.archive_batch_id',
                'schedule_archives.semester',
                'schedule_archives.semester_name',
                'schedule_archives.school_year',
                'schedule_archives.total_subjects',
                'schedule_archives.total_schedules',
                'schedule_archives.archived_at',
                'schedule_archives.next_semester',
                'schedule_archives.next_school_year',
                'users.name as archived_by_name'
            )
            ->latest('schedule_archives.archived_at')
            ->take(15)
            ->get();
    }

    public function archivedHistoryRecords(): Collection
    {
        if (! $this->selectedHistoricalSemester) {
            return collect();
        }

        if (str_starts_with($this->selectedHistoricalSemester, 'legacy:')) {
            return $this->legacyArchivedHistoryRecords((int) str_replace('legacy:', '', $this->selectedHistoricalSemester));
        }

        $batchId      = str_replace('batch:', '', $this->selectedHistoricalSemester);
        $scheduleRows = Schedule::with([
            'subject:id,edp_code,subject_code,description,units',
            'faculty:id,full_name,employee_id',
            'room:id,room_name,type',
        ])
            ->archived()
            ->where('archive_batch', $batchId)
            ->when($this->archiveFilterDepartment !== '', fn ($query) => $query->where(function ($inner) {
                $inner->where('department', $this->archiveFilterDepartment)
                    ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery->where('department', $this->archiveFilterDepartment));
            }))
            ->orderBy('section')
            ->orderBy('day')
            ->get();

        $records = $scheduleRows->map(function (Schedule $schedule) {
            return (object) [
                'edp_code'          => $schedule->edp_code ?: $schedule->subject?->edp_code ?: 'N/A',
                'subject_code'      => $schedule->subject?->subject_code ?: 'N/A',
                'descriptive_title' => $schedule->subject?->description ?: 'N/A',
                'section'           => $schedule->section ?: 'N/A',
                'instructor_name'   => $schedule->faculty?->full_name ?: 'Unassigned',
                'units'             => $schedule->subject?->units ?: 'N/A',
                'day'               => $schedule->day ?: 'N/A',
                'start_time'        => $schedule->start_time,
                'end_time'          => $schedule->end_time,
                'status'            => $schedule->status ?: 'archived',
                'department'        => $schedule->department ?: $schedule->subject?->department,
                'room_name'         => $schedule->room?->room_name ?: 'Unassigned',
                'room_type'         => $schedule->room?->type ?: 'N/A',
                'room_id'           => $schedule->room_id,
                'faculty_id'        => $schedule->faculty_id,
                'schedule_id'       => $schedule->id,
            ];
        });

        $scheduledSubjectIds = $scheduleRows->pluck('subject_id')->filter()->unique();
        $unscheduledSubjects = Subject::archived()
            ->where('archive_batch', $batchId)
            ->when($this->archiveFilterDepartment !== '', fn ($query) => $query->where('department', $this->archiveFilterDepartment))
            ->when($scheduledSubjectIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $scheduledSubjectIds))
            ->orderBy('edp_code')
            ->get();

        return $records
            ->concat($unscheduledSubjects->map(fn (Subject $subject) => (object) [
                'edp_code'          => $subject->edp_code ?: 'N/A',
                'subject_code'      => $subject->subject_code ?: 'N/A',
                'descriptive_title' => $subject->description ?: 'N/A',
                'section'           => $subject->section ?: 'N/A',
                'instructor_name'   => $subject->faculty_id ? (Faculty::find($subject->faculty_id)?->full_name ?: 'Assigned') : 'Unassigned',
                'units'             => $subject->units ?: 'N/A',
                'day'               => 'Unscheduled',
                'start_time'        => null,
                'end_time'          => null,
                'status'            => 'archived',
                'department'        => $subject->department,
                'room_name'         => 'Unscheduled',
                'room_type'         => 'N/A',
                'room_id'           => null,
                'faculty_id'        => $subject->faculty_id ?? null,
                'schedule_id'       => null,
            ]))
            ->values();
    }

    // =========================================================================
    // COUNTS / COMPUTED
    // =========================================================================

    public function activeSubjectCount(): int
    {
        if (! Schema::hasTable('subjects')) {
            return 0;
        }

        return Subject::activeTerm($this->semester, $this->school_year)->count();
    }

    public function activeScheduleCount(): int
    {
        if (! Schema::hasTable('schedules')) {
            return 0;
        }

        return Schedule::activeTerm($this->semester, $this->school_year)->count();
    }

    private function storedActiveSubjectCount(): int
    {
        $period = Setting::getAcademicPeriod();

        return Schema::hasTable('subjects')
            ? Subject::activeTerm($period['semester'], $period['school_year'])->count()
            : 0;
    }

    private function storedActiveScheduleCount(): int
    {
        $period = Setting::getAcademicPeriod();

        return Schema::hasTable('schedules')
            ? Schedule::activeTerm($period['semester'], $period['school_year'])->count()
            : 0;
    }

    public function currentEdpPrefix(): string
    {
        return Setting::edpTermPrefix((string) $this->school_year, (string) $this->semester);
    }

    public function previewNextAcademicPeriod(): array
    {
        return Setting::nextAcademicPeriod($this->semester, $this->school_year);
    }

    // =========================================================================
    // PRIVATE / INTERNAL HELPERS
    // =========================================================================

    private function academicPeriodChanged(): bool
    {
        $period = Setting::getAcademicPeriod();

        return $period['semester'] !== Setting::normalizeSemester($this->semester)
            || $period['school_year'] !== $this->school_year;
    }

    private function validateTimeIncrement(string $time): bool
    {
        $parts = explode(':', $time);

        return count($parts) === 2 && in_array((int) $parts[1], [0, 30], true);
    }

    private function validateAllTimeIncrements(): bool
    {
        return $this->validateTimeIncrement((string) $this->day_start)
            && $this->validateTimeIncrement((string) $this->day_end);
    }

    private function hasScheduleConflicts(): array
    {
        $conflicts  = [];
        $newStart   = Carbon::parse($this->day_start);
        $newEnd     = Carbon::parse($this->day_end);
        $activeDays = Setting::normalizeActiveDays($this->active_days);

        $schedules = Schedule::activeTerm($this->semester, $this->school_year)
            ->with('subject:id,subject_code,is_practicum')
            ->whereNotNull('day')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();

        foreach ($schedules as $schedule) {
            if (is_null($schedule->day) || is_null($schedule->start_time) || is_null($schedule->end_time)) {
                continue;
            }

            $scheduleStart = Carbon::parse($schedule->start_time);
            $scheduleEnd   = Carbon::parse($schedule->end_time);

            if (! in_array($schedule->day, $activeDays, true)) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day'          => $schedule->day,
                    'time'         => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue'        => 'is scheduled on a disabled day',
                ];

                continue;
            }

            if ($scheduleStart < $newStart) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day'          => $schedule->day,
                    'time'         => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue'        => 'starts before the new day start',
                ];
            }

            if ($scheduleEnd > $newEnd) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day'          => $schedule->day,
                    'time'         => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue'        => 'ends after the new day end',
                ];
            }
        }

        return $conflicts;
    }

    private function semesterEndValidationBlockers(): array
    {
        $period    = Setting::getAcademicPeriod();
        $subjects  = Subject::activeTerm($period['semester'], $period['school_year'])
            ->withCount(['schedules as schedules_count' => fn ($query) => $query->activeTerm($period['semester'], $period['school_year'])])
            ->get();
        $schedules = Schedule::activeTerm($period['semester'], $period['school_year'])
            ->with(['subject', 'room'])
            ->get();
        $blockers  = [];

        if ($subjects->isEmpty()) {
            $blockers[] = 'No active subjects exist in the current workspace.';
        }

        $schedulableSubjectIds = $subjects
            ->filter(fn (Subject $subject) => ! $subject->is_practicum)
            ->pluck('id');

        $nonPracticumSchedules = $schedules->filter(
            fn (Schedule $schedule) => $schedulableSubjectIds->contains($schedule->subject_id)
        );

        if ($schedulableSubjectIds->isNotEmpty() && $nonPracticumSchedules->isEmpty()) {
            $blockers[] = 'No generated schedules exist in the current workspace.';
        }

        $unscheduled = $subjects
            ->filter(fn (Subject $subject) => ! $subject->is_practicum)
            ->filter(fn (Subject $subject) => (int) $subject->schedules_count < max(1, (int) $subject->meetings_per_week))
            ->take(8)
            ->map(fn (Subject $subject) => "{$subject->subject_code} needs " . max(0, (int) $subject->meetings_per_week - (int) $subject->schedules_count) . ' more meeting(s).')
            ->values()
            ->all();

        if ($unscheduled !== []) {
            $blockers[] = 'Required subjects are not fully scheduled: ' . implode(' ', $unscheduled);
        }

        $unfinalizedCount = $schedules
            ->filter(fn (Schedule $schedule) => $schedule->status !== Schedule::STATUS_FINALIZED)
            ->count();

        if ($unfinalizedCount > 0) {
            $blockers[] = "{$unfinalizedCount} schedule row(s) are not finalized.";
        }

        $invalidSchedules = $this->activeSchedulePlacementConflicts($nonPracticumSchedules);

        if ($invalidSchedules !== []) {
            $blockers[] = 'Unresolved conflicts remain: ' . implode(' ', array_slice($invalidSchedules, 0, 6));
        }

        return $blockers;
    }

    private function activeSchedulePlacementConflicts(Collection $schedules): array
    {
        $conflictService = app(ScheduleConflictService::class);
        $conflicts       = [];

        foreach ($schedules as $schedule) {
            if (! $schedule->subject || ! $schedule->room || ! $schedule->day || ! $schedule->start_time || ! $schedule->end_time) {
                $conflicts[] = ($schedule->subject?->subject_code ?? 'Unknown subject') . ' has incomplete schedule data.';
                continue;
            }

            $result = $conflictService->validatePlacement(
                $schedule->subject,
                $schedule->room,
                (string) $schedule->day,
                Carbon::parse($schedule->start_time)->format('H:i:s'),
                Carbon::parse($schedule->end_time)->format('H:i:s'),
                (int) $schedule->id,
                false
            );

            if (($result['status'] ?? true) === false) {
                $conflicts[] = ($schedule->subject?->subject_code ?? 'Unknown subject') . ': ' . ($result['title'] ?? $result['message'] ?? 'conflict detected');
            }
        }

        return array_values(array_unique($conflicts));
    }

    private function persistSetting(string $key, mixed $value, string $action = 'updated'): Setting
    {
        $oldValue = Setting::where('key', $key)->first()?->value;
        $newValue = is_array($value) ? json_encode(array_values($value)) : (string) $value;

        $setting = Setting::updateOrCreate(['key' => $key], [
            'value'           => $newValue,
            'last_updated_by' => auth()->id(),
            'last_updated_at' => now(),
        ]);

        if ($oldValue !== $newValue && auth()->id()) {
            SettingChangeLog::create([
                'user_id'     => auth()->id(),
                'setting_key' => $key,
                'old_value'   => $oldValue,
                'new_value'   => $newValue,
                'action'      => $action,
                'changed_at'  => now(),
            ]);
        }

        return $setting;
    }

    private function currentWorkspaceSubjectsQuery(array $period): Builder
    {
        return Subject::activeTerm($period['semester'], $period['school_year']);
    }

    private function currentWorkspaceSchedulesQuery(array $period): Builder
    {
        return Schedule::activeTerm($period['semester'], $period['school_year']);
    }

    private function activeSubjectsBaseQuery(): Builder
    {
        $query = Subject::query();

        if (Schema::hasColumn('subjects', 'is_archived')) {
            $query->where('is_archived', false);
        }

        return $query;
    }

    private function activeSchedulesBaseQuery(): Builder
    {
        $query = Schedule::query();

        if (Schema::hasColumn('schedules', 'is_archived')) {
            $query->where('is_archived', false);
        }

        return $query;
    }

    private function nextArchiveBatchId(string $semester, string $schoolYear): string
    {
        $year = preg_match('/^(\d{4})-\d{4}$/', $schoolYear, $matches)
            ? $matches[1]
            : (string) now()->year;

        $base   = 'ARCH-' . $year . '-' . Setting::semesterCode($semester) . '-';
        $latest = DB::table('schedule_archives')
            ->where('archive_batch_id', 'like', $base . '%')
            ->orderByDesc('archive_batch_id')
            ->value('archive_batch_id');

        $nextSequence = $latest ? ((int) substr((string) $latest, -3)) + 1 : 1;

        return $base . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    private function buildArchiveSnapshot(Collection $subjects, Collection $schedules, string $archiveBatchId): string
    {
        return json_encode([
            'archive_batch_id' => $archiveBatchId,
            'subjects'         => $subjects->map(fn (Subject $subject) => $subject->only([
                'id', 'edp_code', 'subject_code', 'section', 'description', 'major',
                'year_level', 'department', 'units', 'duration_hours', 'type',
                'subject_type', 'requires_lab', 'preferred_room_type', 'preferred_room_id',
                'specialization', 'meetings_per_week', 'faculty_id',
            ]))->values(),
            'schedules'        => $schedules->map(fn (Schedule $schedule) => [
                'id'                => $schedule->id,
                'subject_id'        => $schedule->subject_id,
                'room_id'           => $schedule->room_id,
                'faculty_id'        => $schedule->faculty_id,
                'edp_code'          => $schedule->edp_code ?: $schedule->subject?->edp_code,
                'subject_code'      => $schedule->subject?->subject_code,
                'descriptive_title' => $schedule->subject?->description,
                'department'        => $schedule->department,
                'major'             => $schedule->major,
                'year_level'        => $schedule->year_level,
                'section'           => $schedule->section,
                'day'               => $schedule->day,
                'start_time'        => $schedule->start_time,
                'end_time'          => $schedule->end_time,
                'duration_hours'    => $schedule->duration_hours,
                'meetings_per_week' => $schedule->meetings_per_week,
                'status'            => $schedule->status,
                'faculty_name'      => $schedule->faculty?->full_name,
                'room_name'         => $schedule->room?->room_name,
                'room_type'         => $schedule->room?->type,
                'finalized_by'      => $schedule->user?->name,
                'archived_at'       => now()->toDateTimeString(),
            ])->values(),
        ]);
    }

    private function subjectAlreadyCopiedIntoActiveTerm(Subject $source, array $period): bool
    {
        return Subject::activeTerm($period['semester'], $period['school_year'])
            ->where('copied_from_id', $source->id)
            ->exists();
    }

    private function sameOfferingExistsInActiveTerm(Subject $source, array $period): bool
    {
        return Subject::activeTerm($period['semester'], $period['school_year'])
            ->where('subject_code', $source->subject_code)
            ->where('section', $source->section)
            ->where('major', $source->major)
            ->where('year_level', $source->year_level)
            ->where('department', $source->department)
            ->exists();
    }

    private function legacyArchivedHistoryRecords(int $archiveId): Collection
    {
        if (! Schema::hasTable('schedule_archives')) {
            return collect();
        }

        $archive = DB::table('schedule_archives')->find($archiveId);

        if (! $archive || blank($archive->schedule_data)) {
            return collect();
        }

        $payload = json_decode($archive->schedule_data, true) ?: [];
        $rows    = collect($payload['schedules'] ?? (array_is_list($payload) ? $payload : []));

        return $rows->map(fn (array $row) => (object) [
            'edp_code'          => $row['edp_code'] ?? 'N/A',
            'subject_code'      => $row['subject_code'] ?? 'N/A',
            'descriptive_title' => $row['descriptive_title'] ?? $row['description'] ?? 'N/A',
            'section'           => $row['section'] ?? 'N/A',
            'instructor_name'   => $row['faculty_name'] ?? 'Unassigned',
            'units'             => $row['units'] ?? 'N/A',
            'day'               => $row['day'] ?? 'N/A',
            'start_time'        => $row['start_time'] ?? null,
            'end_time'          => $row['end_time'] ?? null,
            'status'            => $row['status'] ?? 'archived',
            'department'        => $row['department'] ?? 'N/A',
        ])->values();
    }

    private function ensureLifecycleSchema(): void
    {
        $requirements = [
            'subjects'          => ['semester', 'school_year', 'academic_year', 'workspace_key', 'is_archived', 'archived_at', 'copied_from_id', 'archive_batch', 'requires_lab', 'preferred_room_type', 'preferred_room_id'],
            'schedules'         => ['semester', 'school_year', 'academic_year', 'workspace_key', 'is_archived', 'archived_at', 'archive_batch'],
            'schedule_archives' => ['archive_batch_id', 'semester', 'total_subjects', 'next_semester', 'next_school_year'],
        ];

        $missing = [];

        foreach ($requirements as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = "{$table}.{$column}";
                }
            }
        }

        if ($missing !== []) {
            throw new RuntimeException('Run the semester lifecycle migrations first. Missing: ' . implode(', ', $missing));
        }
    }

    private function semesterLabel(): string
    {
        return Setting::semesterLabel($this->semester);
    }

    public function generateNextAcademicYear(?string $academicYear = null): string
    {
        return Setting::nextAcademicYear($academicYear ?: (string) $this->school_year);
    }

    private function generateNextEdpCode(string $major, int $yearLevel, ?string $academicYear = null): string
    {
        $period = Setting::getAcademicPeriod();

        return Subject::generateEdpCode(
            $major,
            $yearLevel,
            $academicYear ?: $period['school_year'],
            $period['semester']
        );
    }

    // =========================================================================
    // RETRIEVAL GUARD HELPERS
    // =========================================================================

    private function retrievalGuardKey(): string
    {
        return 'retrieved_for_' . Setting::workspaceKey();
    }

    private function hasRetrievedCurrentTerm(): bool
    {
        return Setting::getBoolean($this->retrievalGuardKey(), false);
    }

    private function markRetrievedCurrentTerm(): void
    {
        Setting::setValue($this->retrievalGuardKey(), '1', auth()->id());
    }

    private function clearRetrievedCurrentTerm(): void
    {
        Setting::setValue($this->retrievalGuardKey(), '0', auth()->id());
    }

    // =========================================================================
    // RETRIEVE WIZARD — Reset all wizard state
    // =========================================================================

    private function resetRetrieveState(): void
    {
        $this->showRetrieveModal        = false;
        $this->showCompatibilityStep    = false;
        $this->showRetrieveConfirmation = false;
        $this->retrieveArchiveBatch     = null;
        $this->matchingArchive          = null;
        $this->compatibilityReport      = null;
        $this->compatibilityResolution  = 'keep_current';
        $this->archiveAcknowledged      = false;
        $this->retrieveMode             = RetrieveMode::SUBJECTS_ONLY;
    }

    // =========================================================================
    // AUDIT HISTORY EXCEL EXPORT
    // =========================================================================

    public function downloadAuditHistory(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $records = $this->archivedHistoryRecords();
        $batch   = $this->selectedHistoricalSemester
            ? str_replace(['batch:', 'legacy:'], '', $this->selectedHistoricalSemester)
            : 'audit';

        $filename = 'audit-history-' . $batch . '.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            // ── Build conflict index ──────────────────────────────────────────
            // Indexed by "day|start|end" → list of records for that slot.
            // We check room conflicts, faculty conflicts, and section conflicts.
            $roomSlotIndex    = [];  // "room_id|day|start|end"              → [edp_codes]
            $facultySlotIndex = [];  // "faculty_id|day|start|end"            → [edp_codes]
            $sectionSlotIndex = [];  // "year_level-section|dept|day|start|end" → [edp_codes]

            /**
             * Extract the year-level digit from an EDP code.
             *
             * EDP codes follow the pattern:  XX-YYYY{Y}NNN…
             * The year-level digit sits at index 6 (0-based) — i.e. the 4th
             * numeric digit after the dash.
             *
             * Examples:
             *   IT-2611001  → index 6 = '1'  (1st Year)
             *   IT-2612001  → index 6 = '2'  (2nd Year)
             *   IT-2613001  → index 6 = '3'  (3rd Year)
             *   IT-2614001  → index 6 = '4'  (4th Year)
             *
             * Returns the single digit string, or '' when the code is too
             * short or the character is not a digit.
             */
            $extractYearLevel = static function (string $edpCode): string {
                $digit = $edpCode[6] ?? '';
                return ctype_digit($digit) ? $digit : '';
            };

            foreach ($records as $rec) {
                if (! $rec->day || $rec->day === 'N/A' || $rec->day === 'Unscheduled' || ! $rec->start_time || ! $rec->end_time) {
                    continue;
                }

                $start = \Carbon\Carbon::parse($rec->start_time)->format('H:i');
                $end   = \Carbon\Carbon::parse($rec->end_time)->format('H:i');

                if (! empty($rec->room_id)) {
                    $roomKey = $rec->room_id . '|' . $rec->day . '|' . $start . '|' . $end;
                    $roomSlotIndex[$roomKey][] = $rec->edp_code;
                }

                if (! empty($rec->faculty_id)) {
                    $facultyKey = $rec->faculty_id . '|' . $rec->day . '|' . $start . '|' . $end;
                    $facultySlotIndex[$facultyKey][] = $rec->edp_code;
                }

                if (! empty($rec->section) && $rec->section !== 'N/A') {
                    $yearLevel  = $extractYearLevel((string) $rec->edp_code);
                    // Combine year level + section so that "1-A" and "3-A" are
                    // treated as entirely different student groups and never
                    // flagged as conflicting with each other.
                    $yearSection = $yearLevel !== '' ? $yearLevel . '-' . $rec->section : $rec->section;
                    $sectionKey  = $yearSection . '|' . $rec->department . '|' . $rec->day . '|' . $start . '|' . $end;
                    $sectionSlotIndex[$sectionKey][] = $rec->edp_code;
                }
            }

            fputcsv($out, [
                'EDP Code', 'Subject Code', 'Descriptive Title', 'Section',
                'Instructor', 'Units', 'Day', 'Start Time', 'End Time',
                'Room', 'Room Type', 'Status', 'Department', 'Conflicts',
            ]);

            foreach ($records as $record) {
                $conflicts = [];

                if ($record->day && $record->day !== 'N/A' && $record->day !== 'Unscheduled' && $record->start_time && $record->end_time) {
                    $start = \Carbon\Carbon::parse($record->start_time)->format('H:i');
                    $end   = \Carbon\Carbon::parse($record->end_time)->format('H:i');

                    // Room double-booking
                    if (! empty($record->room_id)) {
                        $roomKey     = $record->room_id . '|' . $record->day . '|' . $start . '|' . $end;
                        $roomConflicts = array_filter($roomSlotIndex[$roomKey] ?? [], fn ($c) => $c !== $record->edp_code);
                        if (! empty($roomConflicts)) {
                            $conflicts[] = 'Room conflict with: ' . implode(', ', $roomConflicts);
                        }
                    }

                    // Faculty double-booking
                    if (! empty($record->faculty_id)) {
                        $facultyKey      = $record->faculty_id . '|' . $record->day . '|' . $start . '|' . $end;
                        $facultyConflicts = array_filter($facultySlotIndex[$facultyKey] ?? [], fn ($c) => $c !== $record->edp_code);
                        if (! empty($facultyConflicts)) {
                            $conflicts[] = 'Faculty conflict with: ' . implode(', ', $facultyConflicts);
                        }
                    }

                    // Section time overlap — scoped to the same year level so that
                    // e.g. 3rd-Year Section A and 1st-Year Section A are never
                    // flagged as conflicting with each other.
                    if (! empty($record->section) && $record->section !== 'N/A') {
                        $yearLevel        = $extractYearLevel((string) $record->edp_code);
                        $yearSection      = $yearLevel !== '' ? $yearLevel . '-' . $record->section : $record->section;
                        $sectionKey       = $yearSection . '|' . $record->department . '|' . $record->day . '|' . $start . '|' . $end;
                        $sectionConflicts = array_filter($sectionSlotIndex[$sectionKey] ?? [], fn ($c) => $c !== $record->edp_code);
                        if (! empty($sectionConflicts)) {
                            $conflicts[] = 'Section conflict with: ' . implode(', ', $sectionConflicts);
                        }
                    }
                }

                fputcsv($out, [
                    $record->edp_code,
                    $record->subject_code,
                    $record->descriptive_title,
                    $record->section,
                    $record->instructor_name,
                    $record->units,
                    $record->day,
                    $record->start_time ? \Carbon\Carbon::parse($record->start_time)->format('h:i A') : '',
                    $record->end_time   ? \Carbon\Carbon::parse($record->end_time)->format('h:i A') : '',
                    $record->room_name  ?? 'Unassigned',
                    $record->room_type  ?? 'N/A',
                    str_replace('_', ' ', $record->status),
                    $record->department,
                    empty($conflicts) ? 'None' : implode(' | ', $conflicts),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // =========================================================================
    // DASHBOARD REDIRECT HELPER
    // =========================================================================

    private function getDashboardUrl(): string
    {
        $role = auth()->user()?->role;

        return match ($role) {
            'registrar' => route('registrar.dashboard'),
            default     => route('admin.dashboard'),
        };
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function render()
    {
        $nextPeriod = $this->previewNextAcademicPeriod();

        return view('livewire.global-settings', [
            'archiveBatches'           => $this->archiveHistoryBatches(),
            'archivedSemesterOptions'  => $this->archivedSemesterOptions(),
            'retrievableArchiveOptions'=> $this->retrievableArchiveOptions(),
            'archivedHistoryRecords'   => $this->archivedHistoryRecords(),
            'changeHistory'            => $this->changeHistory,
            'brickDuration'            => self::BRICK_DURATION,
            'lunchStart'               => self::LUNCH_START,
            'lunchEnd'                 => self::LUNCH_END,
            'availableDays'            => Setting::ALL_SCHEDULE_DAYS,
            'activeSubjectsCount'      => $this->activeSubjectCount(),
            'activeSchedulesCount'     => $this->activeScheduleCount(),
            'currentEdpPrefix'         => $this->currentEdpPrefix(),
            'nextPeriod'               => $nextPeriod,
            'systemReady'              => $this->system_ready,
            'setupChecklist'           => $this->setupChecklist,
            'setupComplete'            => $this->setupComplete,
            'systemReadyMeta'          => Setting::getSystemReadyMeta(),
        ])->layout('layouts.app');
    }
}