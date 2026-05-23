<?php

namespace App\Livewire;

use App\Models\Faculty;
use App\Models\Activity;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\SettingChangeLog;
use App\Models\Subject;
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

    public $day_start;

    public $day_end;

    public $school_year;

    public $semester;

    public $semester_name;

    public array $active_days = [];

    public bool $config_locked = true;

    public bool $maintenance_mode = false;

    public bool $confirmingReset = false;

    public bool $archiveAcknowledged = false;

    public bool $showRetrieveModal = false;

    public ?string $retrieveArchiveBatch = null;

    public ?string $selectedHistoricalSemester = null;

    public array $changeHistory = [];

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['admin', 'registrar'], true)) {
            abort(403, 'Unauthorized access to system configurations.');
        }

        $this->loadAllSettings();
        $this->loadChangeHistory();
    }

    public function updatedSemester(): void
    {
        $this->semester = Setting::normalizeSemester($this->semester);
        $this->semester_name = Setting::semesterDisplayName($this->semester, $this->school_year);
    }

    public function updatedSchoolYear(): void
    {
        $this->semester_name = Setting::semesterDisplayName($this->semester, $this->school_year);
    }

    private function loadAllSettings(): void
    {
        $scheduleSettings = Setting::getScheduleSettings();
        $period = Setting::getAcademicPeriod();

        $this->active_days = $scheduleSettings['active_days'];
        $this->day_start = $scheduleSettings['start_time'];
        $this->day_end = $scheduleSettings['end_time'];
        $this->school_year = $period['school_year'];
        $this->semester = $period['semester'];
        $this->semester_name = $period['semester_name'];
        $this->config_locked = Setting::isConfigLocked();
        $this->maintenance_mode = Setting::getBoolean('maintenance_mode', false);
    }

    private function loadChangeHistory(): void
    {
        $this->changeHistory = SettingChangeLog::with('user:id,name')
            ->latest('changed_at')
            ->take(12)
            ->get()
            ->toArray();
    }

    public function toggleLock(): void
    {
        $this->config_locked = ! $this->config_locked;
        $this->persistSetting('config_locked', $this->config_locked ? '1' : '0', $this->config_locked ? 'locked' : 'unlocked');

        $this->dispatch('notify', [
            'type' => $this->config_locked ? 'info' : 'warning',
            'message' => $this->config_locked
                ? 'Configuration locked. Changes are disabled.'
                : 'Configuration unlocked. Proceed with care.',
        ]);
    }

    public function toggleMaintenanceMode(): void
    {
        $this->maintenance_mode = ! $this->maintenance_mode;
        $this->persistSetting('maintenance_mode', $this->maintenance_mode ? '1' : '0');

        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => $this->maintenance_mode
                ? 'Maintenance mode is on. Deans cannot modify subjects.'
                : 'Maintenance mode is off.',
        ]);
    }

    public function save(): void
    {
        if ($this->config_locked) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Configuration is locked. Unlock it before saving changes.',
            ]);

            return;
        }

        $this->validate([
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in(Setting::SEMESTERS)],
            'semester_name' => ['required', 'string', 'min:3'],
            'active_days' => ['required', 'array', 'min:1'],
            'active_days.*' => ['required', 'string', Rule::in(Setting::ALL_SCHEDULE_DAYS)],
            'day_start' => ['required', 'date_format:H:i'],
            'day_end' => ['required', 'date_format:H:i', 'after:day_start'],
        ]);

        $this->semester = Setting::normalizeSemester($this->semester);
        $this->active_days = Setting::normalizeActiveDays($this->active_days);
        $this->semester_name = trim((string) $this->semester_name);

        if (! $this->validateAllTimeIncrements()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'All times must use 30-minute increments.',
            ]);

            return;
        }

        if (Carbon::parse($this->day_start)->diffInMinutes(Carbon::parse($this->day_end)) < (self::BRICK_DURATION * 60)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'The school day is too short for one scheduling brick.',
            ]);

            return;
        }

        if ($this->academicPeriodChanged() && ($this->storedActiveSubjectCount() + $this->storedActiveScheduleCount()) > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
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
                'type' => 'error',
                'message' => 'Existing schedules conflict with the new day bounds.',
                'detail' => $detail,
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
            $this->loadChangeHistory();
            $this->dispatch('settings-updated')->to(MasterGrid::class);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'System configuration saved and locked.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Save failed: '.$e->getMessage(),
            ]);
        }
    }

    public function endSemester(): void
    {
        if (! $this->archiveAcknowledged) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Confirm the archive safeguard before ending the semester.',
            ]);

            return;
        }

        try {
            $result = DB::transaction(function () {
                $archive = $this->archiveCurrentSemester();
                $nextPeriod = $this->advanceAcademicPeriod();

                if (Schema::hasTable('schedule_archives')) {
                    DB::table('schedule_archives')
                        ->where('archive_batch_id', $archive['archive_batch_id'])
                        ->update([
                            'next_semester' => $nextPeriod['semester'],
                            'next_school_year' => $nextPeriod['school_year'],
                            'updated_at' => now(),
                        ]);
                }

                return compact('archive', 'nextPeriod');
            });

            $this->confirmingReset = false;
            $this->archiveAcknowledged = false;
            $this->loadAllSettings();
            $this->loadChangeHistory();

            $this->dispatch('settings-updated')->to(MasterGrid::class);
            $this->dispatch('subjectUpdated');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Archived {$result['archive']['archive_batch_id']} and advanced to {$result['nextPeriod']['semester_name']}.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'End semester failed: '.$e->getMessage(),
            ]);
        }
    }

    private function archiveCurrentSemester(): array
    {
        $this->ensureLifecycleSchema();

        $period = Setting::getAcademicPeriod();
        $nextPeriod = Setting::nextAcademicPeriod($period['semester'], $period['school_year']);
        $now = now();

        $subjects = $this->currentWorkspaceSubjectsQuery($period)
            ->lockForUpdate()
            ->get();

        $schedules = $this->currentWorkspaceSchedulesQuery($period)
            ->with(['subject:id,edp_code,subject_code,description,units', 'faculty:id,full_name,employee_id'])
            ->lockForUpdate()
            ->get();

        if ($subjects->isEmpty() && $schedules->isEmpty()) {
            throw new RuntimeException('There are no active subjects or schedules to archive.');
        }

        $archiveBatchId = $this->nextArchiveBatchId($period['semester'], $period['school_year']);

        foreach ($subjects as $subject) {
            $subject->forceFill([
                'semester' => $period['semester'],
                'school_year' => $period['school_year'],
                'academic_year' => $period['school_year'],
                'workspace_key' => $period['workspace_key'],
                'is_archived' => true,
                'archived_at' => $now,
                'archive_batch' => $archiveBatchId,
            ])->save();
        }

        foreach ($schedules as $schedule) {
            $schedule->forceFill([
                'edp_code' => $schedule->edp_code ?: $schedule->subject?->edp_code,
                'semester' => $period['semester'],
                'school_year' => $period['school_year'],
                'academic_year' => $period['school_year'],
                'workspace_key' => $period['workspace_key'],
                'is_archived' => true,
                'archived_at' => $now,
                'archive_batch' => $archiveBatchId,
            ])->save();
        }

        if (Schema::hasTable('schedule_archives')) {
            DB::table('schedule_archives')->insert([
                'archive_batch_id' => $archiveBatchId,
                'semester' => $period['semester'],
                'semester_name' => $period['semester_name'],
                'school_year' => $period['school_year'],
                'total_subjects' => $subjects->count(),
                'total_schedules' => $schedules->count(),
                'schedule_data' => $this->buildArchiveSnapshot($subjects, $schedules, $archiveBatchId),
                'metadata' => json_encode([
                    'previous_period' => $period,
                    'next_period' => $nextPeriod,
                    'active_edp_prefix' => $period['edp_prefix'],
                ]),
                'archived_by' => auth()->id(),
                'archive_notes' => 'Semester lifecycle archive. Records remain in source tables as locked archived rows.',
                'is_locked' => true,
                'archived_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        SettingChangeLog::create([
            'user_id' => auth()->id(),
            'setting_key' => 'semester_archive',
            'old_value' => $period['semester_name'],
            'new_value' => $archiveBatchId,
            'action' => 'reset',
            'change_reason' => "Archived {$subjects->count()} subjects and {$schedules->count()} schedules.",
            'changed_at' => $now,
        ]);

        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'Archive',
            'module' => 'Semester',
            'description' => 'Archived '.$period['semester_name'],
        ]);

        return [
            'archive_batch_id' => $archiveBatchId,
            'semester' => $period['semester'],
            'school_year' => $period['school_year'],
            'total_subjects' => $subjects->count(),
            'total_schedules' => $schedules->count(),
        ];
    }

    private function advanceAcademicPeriod(): array
    {
        $current = Setting::getAcademicPeriod();
        $next = Setting::nextAcademicPeriod($current['semester'], $current['school_year']);

        $this->persistSetting('semester', $next['semester']);
        $this->persistSetting('school_year', $next['school_year']);
        $this->persistSetting('semester_name', $next['semester_name']);
        $this->persistSetting('config_locked', '1', 'locked');

        return $next;
    }

    public function retrieveArchivedSemester(): void
    {
        $this->ensureLifecycleSchema();

        if (! $this->retrieveArchiveBatch) {
            $this->dispatch('notify', [
                'type' => 'error',
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

                $period = Setting::getAcademicPeriod();
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

                $created = 0;
                $skipped = 0;
                $schedulesCreated = 0;
                $schedulesSkipped = 0;
                $createdSubjectsBySourceId = collect();

                foreach ($sourceSubjects as $source) {
                    if ($this->subjectAlreadyCopiedIntoActiveTerm($source, $period)) {
                        $skipped++;

                        continue;
                    }

                    if ($this->sameOfferingExistsInActiveTerm($source, $period)) {
                        $skipped++;

                        continue;
                    }

                    $newSubject = Subject::create([
                        'edp_code' => Subject::generateEdpCode(
                            $source->major ?: strtok((string) $source->edp_code, '-'),
                            (int) $source->year_level,
                            $period['school_year'],
                            $period['semester']
                        ),
                        'subject_code' => $source->subject_code,
                        'section' => $source->section,
                        'description' => $source->description,
                        'major' => $source->major,
                        'year_level' => $source->year_level,
                        'department' => $source->department,
                        'units' => $source->units,
                        'duration_hours' => $source->duration_hours,
                        'type' => $source->type,
                        'subject_type' => $source->subject_type,
                        'specialization' => $source->specialization,
                        'meetings_per_week' => $source->meetings_per_week,
                        'faculty_id' => $source->faculty_id,
                        'semester' => $period['semester'],
                        'school_year' => $period['school_year'],
                        'academic_year' => $period['school_year'],
                        'workspace_key' => $period['workspace_key'],
                        'is_archived' => false,
                        'archived_at' => null,
                        'copied_from_id' => $source->id,
                        'archive_batch' => null,
                    ]);

                    $createdSubjectsBySourceId->put($source->id, $newSubject);
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

                foreach ($sourceSchedules as $sourceSchedule) {
                    $newSubject = $createdSubjectsBySourceId->get($sourceSchedule->subject_id);

                    if (! $newSubject) {
                        $schedulesSkipped++;

                        continue;
                    }

                    Schedule::create([
                        'subject_id' => $newSubject->id,
                        'room_id' => $sourceSchedule->room_id,
                        'faculty_id' => $sourceSchedule->faculty_id,
                        'user_id' => auth()->id(),
                        'department' => $newSubject->department,
                        'major' => $newSubject->major,
                        'year_level' => $newSubject->year_level,
                        'section' => $newSubject->section,
                        'day' => $sourceSchedule->day,
                        'start_time' => Carbon::parse($sourceSchedule->start_time)->format('H:i:s'),
                        'end_time' => Carbon::parse($sourceSchedule->end_time)->format('H:i:s'),
                        'duration_hours' => $sourceSchedule->duration_hours,
                        'meetings_per_week' => $sourceSchedule->meetings_per_week,
                        'pairing_key' => $this->retrievedPairingKey($sourceSchedule->pairing_key, $pairingKeyMap),
                        'status' => $sourceSchedule->status ?: Schedule::STATUS_PARTIAL,
                        'edp_code' => $newSubject->edp_code,
                        'semester' => $period['semester'],
                        'school_year' => $period['school_year'],
                        'academic_year' => $period['school_year'],
                        'workspace_key' => $period['workspace_key'],
                        'is_archived' => false,
                        'archived_at' => null,
                        'archive_batch' => null,
                    ]);

                    $schedulesCreated++;
                }

                SettingChangeLog::create([
                    'user_id' => auth()->id(),
                    'setting_key' => 'semester_retrieval',
                    'old_value' => $this->retrieveArchiveBatch,
                    'new_value' => $period['semester_name'],
                    'action' => 'created',
                    'change_reason' => "Copied {$created} archived subjects and {$schedulesCreated} schedules into the active semester. {$skipped} subjects and {$schedulesSkipped} schedules skipped.",
                    'changed_at' => now(),
                ]);

                Activity::create([
                    'user_id' => auth()->id(),
                    'action' => 'Retrieve',
                    'module' => 'Semester',
                    'description' => "Retrieved {$archive->semester_name} into {$period['semester_name']}.",
                ]);

                return compact('created', 'skipped', 'schedulesCreated', 'schedulesSkipped', 'archive', 'period');
            });

            $this->showRetrieveModal = false;
            $this->retrieveArchiveBatch = null;
            $this->loadChangeHistory();
            $this->dispatch('subjectUpdated');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Retrieved {$result['created']} subjects and {$result['schedulesCreated']} schedules into {$result['period']['semester_name']}.",
                'detail' => ($result['skipped'] + $result['schedulesSkipped']) > 0
                    ? "{$result['skipped']} subjects and {$result['schedulesSkipped']} schedules were skipped."
                    : '',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Retrieval failed: '.$e->getMessage(),
            ]);
        }
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
                'value' => $archive->archive_batch_id ? 'batch:'.$archive->archive_batch_id : 'legacy:'.$archive->id,
                'batch' => $archive->archive_batch_id,
                'label' => ($archive->semester_name ?: Setting::semesterDisplayName($archive->semester, $archive->school_year)),
                'school_year' => $archive->school_year,
                'badge' => "{$archive->total_subjects} subjects / {$archive->total_schedules} schedules",
                'date' => $archive->archived_at,
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
            ->take(20)
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

        $batchId = str_replace('batch:', '', $this->selectedHistoricalSemester);

        $scheduleRows = Schedule::with([
            'subject:id,edp_code,subject_code,description,units',
            'faculty:id,full_name,employee_id',
        ])
            ->archived()
            ->where('archive_batch', $batchId)
            ->orderBy('section')
            ->orderBy('day')
            ->get();

        $records = $scheduleRows->map(function (Schedule $schedule) {
            return (object) [
                'edp_code' => $schedule->edp_code ?: $schedule->subject?->edp_code ?: 'N/A',
                'subject_code' => $schedule->subject?->subject_code ?: 'N/A',
                'descriptive_title' => $schedule->subject?->description ?: 'N/A',
                'section' => $schedule->section ?: 'N/A',
                'instructor_name' => $schedule->faculty?->full_name ?: 'Unassigned',
                'units' => $schedule->subject?->units ?: 'N/A',
                'day' => $schedule->day ?: 'N/A',
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'status' => $schedule->status ?: 'archived',
                'department' => $schedule->department ?: $schedule->subject?->department,
            ];
        });

        $scheduledSubjectIds = $scheduleRows->pluck('subject_id')->filter()->unique();
        $unscheduledSubjects = Subject::archived()
            ->where('archive_batch', $batchId)
            ->when($scheduledSubjectIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $scheduledSubjectIds))
            ->orderBy('edp_code')
            ->get();

        return $records
            ->concat($unscheduledSubjects->map(fn (Subject $subject) => (object) [
                'edp_code' => $subject->edp_code ?: 'N/A',
                'subject_code' => $subject->subject_code ?: 'N/A',
                'descriptive_title' => $subject->description ?: 'N/A',
                'section' => $subject->section ?: 'N/A',
                'instructor_name' => $subject->faculty_id ? (Faculty::find($subject->faculty_id)?->full_name ?: 'Assigned') : 'Unassigned',
                'units' => $subject->units ?: 'N/A',
                'day' => 'Unscheduled',
                'start_time' => null,
                'end_time' => null,
                'status' => 'archived',
                'department' => $subject->department,
            ]))
            ->values();
    }

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
        $conflicts = [];
        $newStart = Carbon::parse($this->day_start);
        $newEnd = Carbon::parse($this->day_end);
        $activeDays = Setting::normalizeActiveDays($this->active_days);

        $schedules = Schedule::activeTerm($this->semester, $this->school_year)
            ->with('subject:id,subject_code')
            ->get();

        foreach ($schedules as $schedule) {
            $scheduleStart = Carbon::parse($schedule->start_time);
            $scheduleEnd = Carbon::parse($schedule->end_time);

            if (! in_array($schedule->day, $activeDays, true)) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i').' - '.$scheduleEnd->format('H:i'),
                    'issue' => 'is scheduled on a disabled day',
                ];

                continue;
            }

            if ($scheduleStart < $newStart) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i').' - '.$scheduleEnd->format('H:i'),
                    'issue' => 'starts before the new day start',
                ];
            }

            if ($scheduleEnd > $newEnd) {
                $conflicts[] = [
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i').' - '.$scheduleEnd->format('H:i'),
                    'issue' => 'ends after the new day end',
                ];
            }
        }

        return $conflicts;
    }

    private function persistSetting(string $key, mixed $value, string $action = 'updated'): Setting
    {
        $oldValue = Setting::where('key', $key)->first()?->value;
        $newValue = is_array($value) ? json_encode(array_values($value)) : (string) $value;

        $setting = Setting::updateOrCreate(['key' => $key], [
            'value' => $newValue,
            'last_updated_by' => auth()->id(),
            'last_updated_at' => now(),
        ]);

        if ($oldValue !== $newValue && auth()->id()) {
            SettingChangeLog::create([
                'user_id' => auth()->id(),
                'setting_key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'action' => $action,
                'changed_at' => now(),
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

        $base = 'ARCH-'.$year.'-'.Setting::semesterCode($semester).'-';
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
            'subjects' => $subjects->map(fn (Subject $subject) => $subject->only([
                'id',
                'edp_code',
                'subject_code',
                'section',
                'description',
                'major',
                'year_level',
                'department',
                'units',
                'duration_hours',
                'type',
                'subject_type',
                'specialization',
                'meetings_per_week',
                'faculty_id',
            ]))->values(),
            'schedules' => $schedules->map(fn (Schedule $schedule) => [
                'id' => $schedule->id,
                'subject_id' => $schedule->subject_id,
                'room_id' => $schedule->room_id,
                'faculty_id' => $schedule->faculty_id,
                'edp_code' => $schedule->edp_code ?: $schedule->subject?->edp_code,
                'subject_code' => $schedule->subject?->subject_code,
                'descriptive_title' => $schedule->subject?->description,
                'department' => $schedule->department,
                'major' => $schedule->major,
                'year_level' => $schedule->year_level,
                'section' => $schedule->section,
                'day' => $schedule->day,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'duration_hours' => $schedule->duration_hours,
                'meetings_per_week' => $schedule->meetings_per_week,
                'status' => $schedule->status,
                'faculty_name' => $schedule->faculty?->full_name,
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
        $rows = collect($payload['schedules'] ?? (array_is_list($payload) ? $payload : []));

        return $rows->map(fn (array $row) => (object) [
            'edp_code' => $row['edp_code'] ?? 'N/A',
            'subject_code' => $row['subject_code'] ?? 'N/A',
            'descriptive_title' => $row['descriptive_title'] ?? $row['description'] ?? 'N/A',
            'section' => $row['section'] ?? 'N/A',
            'instructor_name' => $row['faculty_name'] ?? 'Unassigned',
            'units' => $row['units'] ?? 'N/A',
            'day' => $row['day'] ?? 'N/A',
            'start_time' => $row['start_time'] ?? null,
            'end_time' => $row['end_time'] ?? null,
            'status' => $row['status'] ?? 'archived',
            'department' => $row['department'] ?? 'N/A',
        ])->values();
    }

    private function ensureLifecycleSchema(): void
    {
        $requirements = [
            'subjects' => ['semester', 'school_year', 'academic_year', 'workspace_key', 'is_archived', 'archived_at', 'copied_from_id', 'archive_batch'],
            'schedules' => ['semester', 'school_year', 'academic_year', 'workspace_key', 'is_archived', 'archived_at', 'archive_batch'],
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

    public function render()
    {
        $nextPeriod = $this->previewNextAcademicPeriod();

        return view('livewire.global-settings', [
            'archiveBatches' => $this->archiveHistoryBatches(),
            'archivedSemesterOptions' => $this->archivedSemesterOptions(),
            'retrievableArchiveOptions' => $this->retrievableArchiveOptions(),
            'archivedHistoryRecords' => $this->archivedHistoryRecords(),
            'changeHistory' => $this->changeHistory,
            'brickDuration' => self::BRICK_DURATION,
            'lunchStart' => self::LUNCH_START,
            'lunchEnd' => self::LUNCH_END,
            'availableDays' => Setting::ALL_SCHEDULE_DAYS,
            'activeSubjectsCount' => $this->activeSubjectCount(),
            'activeSchedulesCount' => $this->activeScheduleCount(),
            'currentEdpPrefix' => $this->currentEdpPrefix(),
            'nextPeriod' => $nextPeriod,
        ])->layout('layouts.app');
    }
}
