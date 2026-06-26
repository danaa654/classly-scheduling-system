<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\SettingChangeLog;
use App\Models\Subject;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
    // System-ready state (NEW)
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

    public bool $archiveAcknowledged = false;

    public bool $showSemesterBlockerModal = false;

    public array $semesterEndBlockers = [];

    // -------------------------------------------------------------------------
    // Archive retrieval
    // -------------------------------------------------------------------------
    public bool $showRetrieveModal = false;

    public bool $showRetrieveConfirmation = false;

    public ?string $retrieveArchiveBatch = null;

    public string $retrieveMode = 'subjects_only';

    public ?string $selectedHistoricalSemester = null;

    public string $archiveFilterSemester = '';

    public string $archiveFilterSchoolYear = '';

    public string $archiveFilterDepartment = '';

    public array $changeHistory = [];

    public ?array $matchingArchive = null;

    public array $workspaceOccupancy = [];

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
        $period = Setting::getAcademicPeriod();

        // Scope logs to the current semester by filtering on entries that were
        // created on or after the most recent semester_archive log (i.e. the
        // moment the last semester ended and a fresh one began).
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

        // Unlocking the config resets the ready flag — the period is being
        // reconfigured so other roles should not see it as live yet.
        if (! $this->config_locked) {
            Setting::markSystemNotReady(auth()->id());
            $this->system_ready = false;
        }

        // Refresh checklist
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

            // Refresh checklist after save — the "config_locked" step should
            // now pass, which may make the checklist fully complete.
            $this->setupChecklist = Setting::getSetupChecklist();
            $this->setupComplete  = Setting::isSetupComplete();

            $this->loadChangeHistory();
            $this->dispatch('settings-updated')->to(MasterGrid::class);

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => 'System configuration saved and locked.'
                    .($this->setupComplete && ! $this->system_ready
                        ? ' All checklist items complete — you can now mark the system as ready.'
                        : ''),
            ]);
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Save failed: '.$e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // SYSTEM READY ACTIONS  ← NEW
    // =========================================================================

    /**
     * Open the "mark as ready" confirmation modal.
     * Guards against marking ready before the checklist is fully satisfied.
     */
    public function openMarkReadyConfirmation(): void
    {
        // Refresh checklist first
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

    /**
     * Persist the "system ready" flag and broadcast to waiting dashboards.
     */
    public function markSystemReady(): void
    {
        // Re-validate checklist at execution time — not just at modal-open time
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
                'description' => 'Marked system as ready for '.Setting::getAcademicPeriod()['semester_name'],
            ]);

            $this->system_ready        = true;
            $this->confirmingMarkReady = false;
            $this->setupChecklist      = Setting::getSetupChecklist();
            $this->setupComplete       = Setting::isSetupComplete();
            $this->loadChangeHistory();

            // Broadcast to all connected dashboards so the "waiting" state
            // resolves in real time without a page refresh.
            $this->dispatch('system-ready-changed', ready: true);

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => 'System is now marked as ready. Other roles will be notified automatically.',
            ]);
        } catch (Throwable $e) {
            $this->confirmingMarkReady = false;

            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Failed to mark system ready: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Open the "revert to not ready" confirmation modal.
     */
    public function openMarkNotReadyConfirmation(): void
    {
        $this->confirmingMarkNotReady = true;
    }

    /**
     * Revert the system back to not-ready (e.g. config needs rework mid-semester).
     */
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
                'description' => 'Reverted system ready status for '.Setting::getAcademicPeriod()['semester_name'],
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
                'message' => 'Failed to revert system ready state: '.$e->getMessage(),
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
                            'next_semester'   => $nextPeriod['semester'],
                            'next_school_year'=> $nextPeriod['school_year'],
                            'updated_at'      => now(),
                        ]);
                }

                // New semester starts — system is NOT ready until Admin/Registrar
                // completes the setup for the incoming period.
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

            // Redirect the admin/registrar to their dashboard after a short delay
            // so the success toast is readable before navigation. Dean-level roles
            // in other sessions already react via the system-ready-changed event.
            $this->dispatch('semester-ended', redirectTo: $this->getDashboardUrl());
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'End semester failed: '.$e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // ARCHIVE / RETRIEVAL
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
                    'previous_period' => $period,
                    'next_period'     => $nextPeriod,
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
            'description' => 'Archived '.$period['semester_name'],
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

    /**
     * Open the retrieval modal and auto-detect matching semester archive.
     */
    public function openRetrieveModal(): void
    {
        $this->ensureLifecycleSchema();

        // One-time retrieval guard: block if already retrieved for the current term
        // and the semester has not been ended yet.
        if ($this->hasRetrievedCurrentTerm()) {
            $period = Setting::getAcademicPeriod();
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'Already retrieved for '.$period['semester_name'].'. You can only retrieve once per semester. End the semester first to start a new term.',
            ]);
            return;
        }

        $matching = $this->findMatchingSemesterArchive();

        if (! $matching) {
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'No archived '.$this->semesterLabel().' semester found.',
            ]);

            return;
        }

        $this->retrieveArchiveBatch = $matching['archive_batch_id'];
        $this->matchingArchive      = $matching;
        $this->workspaceOccupancy   = $this->getWorkspaceOccupancy();
        $this->showRetrieveModal    = true;
    }

    /**
     * Validate and proceed to confirmation before actually retrieving.
     */
    public function proceedToRetrieveConfirmation(): void
    {
        $this->ensureLifecycleSchema();

        if (! $this->retrieveArchiveBatch) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'No archive selected.',
            ]);

            return;
        }

        $this->workspaceOccupancy    = $this->getWorkspaceOccupancy();
        $this->showRetrieveConfirmation = true;
    }

    /**
     * Execute the actual retrieval after user confirmation.
     */
    public function retrieveArchivedSemester(): void
    {
        $this->ensureLifecycleSchema();

        if (! $this->retrieveArchiveBatch) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Select an archived semester to retrieve.',
            ]);

            return;
        }

        try {
            $result = DB::transaction(function () {
                $archive = DB::table('schedule_archives')
                    ->where('archive_batch_id', $this->retrieveArchiveBatch)
                    ->lockForUpdate()
                    ->first();

                if (! $archive) {
                    throw new RuntimeException('The selected archive batch was not found.');
                }

                $period         = Setting::getAcademicPeriod();
                $sourceSubjects = Subject::archived()
                    ->where('archive_batch', $this->retrieveArchiveBatch)
                    ->orderBy('major')
                    ->orderBy('year_level')
                    ->orderBy('edp_code')
                    ->lockForUpdate()
                    ->get();

                if ($sourceSubjects->isEmpty()) {
                    throw new RuntimeException('This archive has no subjects available for retrieval.');
                }

                // Pre-scan: build a map of sourceSubjectId → room_id from archived schedules.
                // This lets us fall back to the schedule's assigned room when the archived
                // subject itself had no preferred_room_id (e.g. it was set by auto-scheduler
                // directly on the schedule record rather than via Manage Rooms).
                $archiveScheduleRoomMap = Schedule::archived()
                    ->where('archive_batch', $this->retrieveArchiveBatch)
                    ->whereNotNull('room_id')
                    ->pluck('room_id', 'subject_id');   // subject_id → first room_id found

                $created                   = 0;
                $updated                   = 0;
                $schedulesCreated          = 0;
                $schedulesUpdated          = 0;
                $schedulesSkipped          = 0;
                // Maps archive source subject ID → active-term Subject model (newly created OR pre-existing).
                // Both paths populate this map so the schedule loop always has a target to work with.
                $subjectsBySourceId        = collect();
                // Tracks which subject IDs in the map were pre-existing (not freshly created),
                // so the schedule loop knows to UPDATE rather than INSERT.
                $preExistingSubjectIds     = collect();

                foreach ($sourceSubjects as $source) {
                    // ── Case A: subject was already copied in a prior retrieval run ──────────
                    $existingByCopy = Subject::activeTerm($period['semester'], $period['school_year'])
                        ->where('copied_from_id', $source->id)
                        ->first();

                    if ($existingByCopy) {
                        // Apply faculty / preferred-room updates so the user's chosen mode
                        // is honoured even when the subject record already exists.
                        $existingByCopy->update(array_filter([
                            'faculty_id'        => $this->retrieveCopiesFaculty()    ? $source->faculty_id       : $existingByCopy->faculty_id,
                            'preferred_room_id' => $this->retrieveCopiesSchedules()
                                ? ($source->preferred_room_id ?? $archiveScheduleRoomMap->get($source->id))
                                : $existingByCopy->preferred_room_id,
                        ], fn ($v) => $v !== null));

                        $subjectsBySourceId->put($source->id, $existingByCopy);
                        $preExistingSubjectIds->push($existingByCopy->id);
                        $updated++;
                        continue;
                    }

                    // ── Case B: same offering exists but wasn't flagged as a copy ────────────
                    $existingByOffering = Subject::activeTerm($period['semester'], $period['school_year'])
                        ->where('subject_code', $source->subject_code)
                        ->where('section',      $source->section)
                        ->where('major',        $source->major)
                        ->where('year_level',   $source->year_level)
                        ->where('department',   $source->department)
                        ->first();

                    if ($existingByOffering) {
                        $existingByOffering->update(array_filter([
                            'faculty_id'        => $this->retrieveCopiesFaculty()    ? $source->faculty_id        : $existingByOffering->faculty_id,
                            'preferred_room_id' => $this->retrieveCopiesSchedules()
                                ? ($source->preferred_room_id ?? $archiveScheduleRoomMap->get($source->id))
                                : $existingByOffering->preferred_room_id,
                            'copied_from_id'    => $source->id, // back-fill the link
                        ], fn ($v) => $v !== null));

                        $subjectsBySourceId->put($source->id, $existingByOffering);
                        $preExistingSubjectIds->push($existingByOffering->id);
                        $updated++;
                        continue;
                    }

                    // ── Case C: brand-new subject — create it ────────────────────────────────
                    $newSubject = Subject::create([
                        'edp_code'           => Subject::generateEdpCode(
                            $source->major ?: strtok((string) $source->edp_code, '-'),
                            (int) $source->year_level,
                            $period['school_year'],
                            $period['semester']
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
                        'faculty_id'          => $this->retrieveCopiesFaculty()   ? $source->faculty_id       : null,
                        'preferred_room_id'   => $this->retrieveCopiesSchedules()
                            ? ($source->preferred_room_id ?? $archiveScheduleRoomMap->get($source->id))
                            : null,
                        'semester'            => $period['semester'],
                        'school_year'         => $period['school_year'],
                        'academic_year'       => $period['school_year'],
                        'workspace_key'       => $period['workspace_key'],
                        'is_archived'         => false,
                        'archived_at'         => null,
                        'copied_from_id'      => $source->id,
                        'archive_batch'       => null,
                    ]);

                    $subjectsBySourceId->put($source->id, $newSubject);
                    $created++;
                }

                $sourceSchedules = Schedule::archived()
                    ->where('archive_batch', $this->retrieveArchiveBatch)
                    ->orderBy('subject_id')
                    ->orderBy('day')
                    ->orderBy('start_time')
                    ->lockForUpdate()
                    ->get();

                $pairingKeyMap = [];

                foreach ($this->retrieveCopiesSchedules() ? $sourceSchedules : collect() as $sourceSchedule) {
                    $targetSubject = $subjectsBySourceId->get($sourceSchedule->subject_id);

                    if (! $targetSubject) {
                        $schedulesSkipped++;
                        continue;
                    }

                    // Faculty + Room mode carries faculty and room but strips time —
                    // the timetable starts fresh while assignments are preserved.
                    $copiesTime = $this->retrieveMode !== 'faculty_room';

                    $targetStatus = $this->retrieveMode === 'full_template'
                        ? ($sourceSchedule->status ?: Schedule::STATUS_PARTIAL)
                        : Schedule::STATUS_PARTIAL;

                    $isPreExisting = $preExistingSubjectIds->contains($targetSubject->id);

                    if ($isPreExisting) {
                        // Subject already existed — update its schedule records rather
                        // than inserting duplicates. We match on subject_id + section
                        // and apply faculty, room, and (optionally) time data.
                        $existingSchedules = Schedule::activeTerm($period['semester'], $period['school_year'])
                            ->where('subject_id', $targetSubject->id)
                            ->get();

                        if ($existingSchedules->isEmpty()) {
                            // No schedule record at all for this subject — create one now.
                            Schedule::create([
                                'subject_id'        => $targetSubject->id,
                                'room_id'           => $sourceSchedule->room_id,
                                'faculty_id'        => $this->retrieveCopiesFaculty() ? $sourceSchedule->faculty_id : null,
                                'user_id'           => auth()->id(),
                                'department'        => $targetSubject->department,
                                'major'             => $targetSubject->major,
                                'year_level'        => $targetSubject->year_level,
                                'section'           => $targetSubject->section,
                                'day'               => $copiesTime ? $sourceSchedule->day : null,
                                'start_time'        => $copiesTime ? Carbon::parse($sourceSchedule->start_time)->format('H:i:s') : null,
                                'end_time'          => $copiesTime ? Carbon::parse($sourceSchedule->end_time)->format('H:i:s') : null,
                                'duration_hours'    => $sourceSchedule->duration_hours,
                                'meetings_per_week' => $sourceSchedule->meetings_per_week,
                                'pairing_key'       => $this->retrievedPairingKey($sourceSchedule->pairing_key, $pairingKeyMap),
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
                            $schedulesCreated++;
                        } else {
                            // Update the first matching existing schedule with the
                            // archived faculty/room (and time when mode includes it).
                            // Additional meeting records beyond the first are left intact.
                            $existingSchedule = $existingSchedules->first();
                            $patch = [
                                'faculty_id' => $this->retrieveCopiesFaculty() ? $sourceSchedule->faculty_id : $existingSchedule->faculty_id,
                                'room_id'    => $sourceSchedule->room_id,
                                'status'     => $targetStatus,
                                'edp_code'   => $targetSubject->edp_code,
                            ];
                            if ($copiesTime) {
                                $patch['day']        = $sourceSchedule->day;
                                $patch['start_time'] = Carbon::parse($sourceSchedule->start_time)->format('H:i:s');
                                $patch['end_time']   = Carbon::parse($sourceSchedule->end_time)->format('H:i:s');
                            }
                            $existingSchedule->update($patch);
                            $schedulesUpdated++;
                        }
                        continue;
                    }

                    // Brand-new subject — insert a fresh schedule record.
                    Schedule::create([
                        'subject_id'        => $targetSubject->id,
                        'room_id'           => $sourceSchedule->room_id,
                        'faculty_id'        => $this->retrieveCopiesFaculty() ? $sourceSchedule->faculty_id : null,
                        'user_id'           => auth()->id(),
                        'department'        => $targetSubject->department,
                        'major'             => $targetSubject->major,
                        'year_level'        => $targetSubject->year_level,
                        'section'           => $targetSubject->section,
                        'day'               => $copiesTime ? $sourceSchedule->day : null,
                        'start_time'        => $copiesTime ? Carbon::parse($sourceSchedule->start_time)->format('H:i:s') : null,
                        'end_time'          => $copiesTime ? Carbon::parse($sourceSchedule->end_time)->format('H:i:s') : null,
                        'duration_hours'    => $sourceSchedule->duration_hours,
                        'meetings_per_week' => $sourceSchedule->meetings_per_week,
                        'pairing_key'       => $this->retrievedPairingKey($sourceSchedule->pairing_key, $pairingKeyMap),
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

                    $schedulesCreated++;
                }

                SettingChangeLog::create([
                    'user_id'       => auth()->id(),
                    'setting_key'   => 'semester_retrieval',
                    'old_value'     => $this->retrieveArchiveBatch,
                    'new_value'     => $period['semester_name'],
                    'action'        => 'created',
                    'change_reason' => "Retrieved mode {$this->retrieveMode}: created {$created} subjects ({$updated} updated), {$schedulesCreated} schedules created, {$schedulesUpdated} schedules updated. {$schedulesSkipped} schedules skipped.",
                    'changed_at'    => now(),
                ]);

                Activity::create([
                    'user_id'     => auth()->id(),
                    'action'      => 'Retrieve',
                    'module'      => 'Semester',
                    'description' => "Retrieved {$archive->semester_name} into {$period['semester_name']}.",
                ]);

                return compact('created', 'updated', 'schedulesCreated', 'schedulesUpdated', 'schedulesSkipped', 'archive', 'period');
            });

            $this->showRetrieveModal    = false;
            $this->showRetrieveConfirmation = false;
            $this->retrieveArchiveBatch = null;
            $this->matchingArchive      = null;
            $this->retrieveMode         = 'subjects_only';

            // Mark that a retrieval has been performed for this semester term.
            // This prevents the admin from retrieving again until the semester ends.
            $this->markRetrievedCurrentTerm();
            $this->alreadyRetrievedCurrentTerm = true;

            $this->loadChangeHistory();
            $this->dispatch('subjectUpdated');

            $created  = $result['created'];
            $updated  = $result['updated'];
            $schCreated = $result['schedulesCreated'];
            $schUpdated = $result['schedulesUpdated'];

            $subjectMsg  = $created > 0 && $updated > 0
                ? "{$created} subjects created, {$updated} updated"
                : ($created > 0 ? "{$created} subjects created" : "{$updated} subjects updated");
            $scheduleMsg = ($schCreated + $schUpdated) > 0
                ? "{$schCreated} schedules created, {$schUpdated} updated"
                : 'no schedules changed';

            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => "Retrieved {$result['period']['semester_name']}: {$subjectMsg}.",
                'detail'  => ucfirst($scheduleMsg) . ($result['schedulesSkipped'] > 0 ? "; {$result['schedulesSkipped']} skipped." : '.'),
            ]);
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Retrieval failed: '.$e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // ARCHIVE QUERY HELPERS
    // =========================================================================

    /**
     * Find the latest archived semester matching the current semester type.
     */
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

    /**
     * Check if current workspace already contains data.
     */
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
                'value'      => $archive->archive_batch_id ? 'batch:'.$archive->archive_batch_id : 'legacy:'.$archive->id,
                'batch'      => $archive->archive_batch_id,
                'label'      => ($archive->semester_name ?: Setting::semesterDisplayName($archive->semester, $archive->school_year)),
                'school_year'=> $archive->school_year,
                'badge'      => "{$archive->total_subjects} subjects / {$archive->total_schedules} schedules",
                'date'       => $archive->archived_at,
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
                'edp_code'         => $schedule->edp_code ?: $schedule->subject?->edp_code ?: 'N/A',
                'subject_code'     => $schedule->subject?->subject_code ?: 'N/A',
                'descriptive_title'=> $schedule->subject?->description ?: 'N/A',
                'section'          => $schedule->section ?: 'N/A',
                'instructor_name'  => $schedule->faculty?->full_name ?: 'Unassigned',
                'units'            => $schedule->subject?->units ?: 'N/A',
                'day'              => $schedule->day ?: 'N/A',
                'start_time'       => $schedule->start_time,
                'end_time'         => $schedule->end_time,
                'status'           => $schedule->status ?: 'archived',
                'department'       => $schedule->department ?: $schedule->subject?->department,
            ];
        });

        $scheduledSubjectIds  = $scheduleRows->pluck('subject_id')->filter()->unique();
        $unscheduledSubjects  = Subject::archived()
            ->where('archive_batch', $batchId)
            ->when($this->archiveFilterDepartment !== '', fn ($query) => $query->where('department', $this->archiveFilterDepartment))
            ->when($scheduledSubjectIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $scheduledSubjectIds))
            ->orderBy('edp_code')
            ->get();

        return $records
            ->concat($unscheduledSubjects->map(fn (Subject $subject) => (object) [
                'edp_code'         => $subject->edp_code ?: 'N/A',
                'subject_code'     => $subject->subject_code ?: 'N/A',
                'descriptive_title'=> $subject->description ?: 'N/A',
                'section'          => $subject->section ?: 'N/A',
                'instructor_name'  => $subject->faculty_id ? (Faculty::find($subject->faculty_id)?->full_name ?: 'Assigned') : 'Unassigned',
                'units'            => $subject->units ?: 'N/A',
                'day'              => 'Unscheduled',
                'start_time'       => null,
                'end_time'         => null,
                'status'           => 'archived',
                'department'       => $subject->department,
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
            // Practicum/OJT subjects have no day/time — skip them entirely.
            ->whereNotNull('day')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();

        foreach ($schedules as $schedule) {
            // Extra safety: skip any schedule row that somehow has null time fields.
            if (is_null($schedule->day) || is_null($schedule->start_time) || is_null($schedule->end_time)) {
                continue;
            }

            $scheduleStart = Carbon::parse($schedule->start_time);
            $scheduleEnd   = Carbon::parse($schedule->end_time);

            if (! in_array($schedule->day, $activeDays, true)) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day'          => $schedule->day,
                    'time'         => $scheduleStart->format('H:i').' - '.$scheduleEnd->format('H:i'),
                    'issue'        => 'is scheduled on a disabled day',
                ];

                continue;
            }

            if ($scheduleStart < $newStart) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day'          => $schedule->day,
                    'time'         => $scheduleStart->format('H:i').' - '.$scheduleEnd->format('H:i'),
                    'issue'        => 'starts before the new day start',
                ];
            }

            if ($scheduleEnd > $newEnd) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day'          => $schedule->day,
                    'time'         => $scheduleStart->format('H:i').' - '.$scheduleEnd->format('H:i'),
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

        // Practicum / OJT subjects are off-campus and intentionally have no
        // room, day, or time. Exclude them from all scheduling completeness checks
        // — they are considered fully handled as-is once they exist in the workspace.
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
            ->map(fn (Subject $subject) => "{$subject->subject_code} needs ".max(0, (int) $subject->meetings_per_week - (int) $subject->schedules_count).' more meeting(s).')
            ->values()
            ->all();

        if ($unscheduled !== []) {
            $blockers[] = 'Required subjects are not fully scheduled: '.implode(' ', $unscheduled);
        }

        $unfinalizedCount = $schedules
            ->filter(fn (Schedule $schedule) => $schedule->status !== Schedule::STATUS_FINALIZED)
            ->count();

        if ($unfinalizedCount > 0) {
            $blockers[] = "{$unfinalizedCount} schedule row(s) are not finalized.";
        }

        // Only run placement conflict checks against non-practicum schedules.
        // Practicum rows have no room/day/time by design and must never be
        // flagged as "incomplete schedule data."
        $invalidSchedules = $this->activeSchedulePlacementConflicts($nonPracticumSchedules);

        if ($invalidSchedules !== []) {
            $blockers[] = 'Unresolved conflicts remain: '.implode(' ', array_slice($invalidSchedules, 0, 6));
        }

        return $blockers;
    }

    private function activeSchedulePlacementConflicts(Collection $schedules): array
    {
        $conflictService = app(ScheduleConflictService::class);
        $conflicts       = [];

        foreach ($schedules as $schedule) {
            if (! $schedule->subject || ! $schedule->room || ! $schedule->day || ! $schedule->start_time || ! $schedule->end_time) {
                $conflicts[] = ($schedule->subject?->subject_code ?? 'Unknown subject').' has incomplete schedule data.';
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
                $conflicts[] = ($schedule->subject?->subject_code ?? 'Unknown subject').': '.($result['title'] ?? $result['message'] ?? 'conflict detected');
            }
        }

        return array_values(array_unique($conflicts));
    }

    private function persistSetting(string $key, mixed $value, string $action = 'updated'): Setting
    {
        $oldValue = Setting::where('key', $key)->first()?->value;
        $newValue = is_array($value) ? json_encode(array_values($value)) : (string) $value;

        $setting = Setting::updateOrCreate(['key' => $key], [
            'value'          => $newValue,
            'last_updated_by'=> auth()->id(),
            'last_updated_at'=> now(),
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

        $base   = 'ARCH-'.$year.'-'.Setting::semesterCode($semester).'-';
        $latest = DB::table('schedule_archives')
            ->where('archive_batch_id', 'like', $base.'%')
            ->orderByDesc('archive_batch_id')
            ->value('archive_batch_id');

        $nextSequence = $latest ? ((int) substr((string) $latest, -3)) + 1 : 1;

        return $base.str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
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
            'edp_code'         => $row['edp_code'] ?? 'N/A',
            'subject_code'     => $row['subject_code'] ?? 'N/A',
            'descriptive_title'=> $row['descriptive_title'] ?? $row['description'] ?? 'N/A',
            'section'          => $row['section'] ?? 'N/A',
            'instructor_name'  => $row['faculty_name'] ?? 'Unassigned',
            'units'            => $row['units'] ?? 'N/A',
            'day'              => $row['day'] ?? 'N/A',
            'start_time'       => $row['start_time'] ?? null,
            'end_time'         => $row['end_time'] ?? null,
            'status'           => $row['status'] ?? 'archived',
            'department'       => $row['department'] ?? 'N/A',
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
            throw new RuntimeException('Run the semester lifecycle migrations first. Missing: '.implode(', ', $missing));
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

    private function retrievedPairingKey(?string $sourcePairingKey, array &$pairingKeyMap): ?string
    {
        if (blank($sourcePairingKey)) {
            return null;
        }

        return $pairingKeyMap[$sourcePairingKey] ??= 'retrieved-'.Str::uuid();
    }

    private function retrieveCopiesFaculty(): bool
    {
        return in_array($this->retrieveMode, ['full_template', 'faculty_only', 'faculty_room'], true);
    }

    private function retrieveCopiesSchedules(): bool
    {
        return in_array($this->retrieveMode, ['full_template', 'room_only', 'time_only', 'faculty_room'], true);
    }

    // =========================================================================
    // RETRIEVAL GUARD HELPERS
    // =========================================================================

    /**
     * Returns the Setting key used to track whether a retrieval has been
     * performed for the current workspace (semester + school_year).
     */
    private function retrievalGuardKey(): string
    {
        return 'retrieved_for_'.Setting::workspaceKey();
    }

    /**
     * Returns true if a retrieval has already been performed for the
     * current semester term and the semester has not yet been ended.
     */
    private function hasRetrievedCurrentTerm(): bool
    {
        return Setting::getBoolean($this->retrievalGuardKey(), false);
    }

    /**
     * Persist the retrieval guard flag for the current term.
     */
    private function markRetrievedCurrentTerm(): void
    {
        Setting::setValue($this->retrievalGuardKey(), '1', auth()->id());
    }

    /**
     * Clear the retrieval guard flag — called when the semester is ended so the
     * next semester starts fresh and allows one retrieval.
     */
    private function clearRetrievedCurrentTerm(): void
    {
        // Clear the OLD workspace key (current term before advancing).
        Setting::setValue($this->retrievalGuardKey(), '0', auth()->id());
    }

    // =========================================================================
    // AUDIT HISTORY EXCEL EXPORT
    // =========================================================================

    /**
     * Stream an Excel-compatible CSV download for the selected historical batch.
     * We use CSV (universally opened by Excel) to avoid requiring PhpSpreadsheet.
     */
    public function downloadAuditHistory(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $records = $this->archivedHistoryRecords();
        $batch   = $this->selectedHistoricalSemester
            ? str_replace(['batch:', 'legacy:'], '', $this->selectedHistoricalSemester)
            : 'audit';

        $filename = 'audit-history-'.$batch.'.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');

            // BOM for Excel UTF-8 detection
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['EDP Code', 'Subject Code', 'Descriptive Title', 'Section', 'Instructor', 'Units', 'Day', 'Start Time', 'End Time', 'Status', 'Department']);

            foreach ($records as $record) {
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
                    str_replace('_', ' ', $record->status),
                    $record->department,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // =========================================================================
    // DASHBOARD REDIRECT HELPER
    // =========================================================================

    /**
     * Returns the dashboard URL for the currently authenticated admin/registrar.
     * After End Semester completes, the user is redirected here so they land on
     * a clean slate rather than staying on the (now-stale) settings page.
     *
     * Adjust the route names below to match your routes/web.php definitions.
     */
    private function getDashboardUrl(): string
    {
        $role = auth()->user()?->role;

        return match ($role) {
            'registrar' => route('registrar-dashboard'),   // ← verify route name
            default     => route('admin-dashboard'),       // ← verify route name
        };
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function render()
    {
        $nextPeriod = $this->previewNextAcademicPeriod();

        return view('livewire.global-settings', [
            'archiveBatches'          => $this->archiveHistoryBatches(),
            'archivedSemesterOptions' => $this->archivedSemesterOptions(),
            'retrievableArchiveOptions'=> $this->retrievableArchiveOptions(),
            'archivedHistoryRecords'  => $this->archivedHistoryRecords(),
            'changeHistory'           => $this->changeHistory,
            'brickDuration'           => self::BRICK_DURATION,
            'lunchStart'              => self::LUNCH_START,
            'lunchEnd'                => self::LUNCH_END,
            'availableDays'           => Setting::ALL_SCHEDULE_DAYS,
            'activeSubjectsCount'     => $this->activeSubjectCount(),
            'activeSchedulesCount'    => $this->activeScheduleCount(),
            'currentEdpPrefix'        => $this->currentEdpPrefix(),
            'nextPeriod'              => $nextPeriod,
            // System-ready data passed explicitly so the blade has clean access
            'systemReady'             => $this->system_ready,
            'setupChecklist'          => $this->setupChecklist,
            'setupComplete'           => $this->setupComplete,
            'systemReadyMeta'         => Setting::getSystemReadyMeta(),
        ])->layout('layouts.app');
    }
}