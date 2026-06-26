<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SubjectUpdatedNotification;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\Schedule;
use App\Models\Setting;
use App\Services\EdpCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ManageSubjects extends Component
{
    use WithFileUploads, WithPagination;

    // UI States
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;
    public $selectedSection = '';
    public $search = '';
    public $selectedDept = '';
    public $selectedYear = '';
    public $selectedMajor = '';
    public string $catalogMode = 'active';
    public string $selectedArchiveBatch = '';

    // Form Fields
    public $subjectId, $edp_code, $subject_code, $section, $description, $department, $units;
    public $major, $year_level;
    public $type = 'Major';
    public bool $requires_lab = false;
    public $preferred_room_type = '';
    public bool $room_override = false;  // true = override default room routing for this subject
    public bool $is_practicum = false;   // true = off-campus/no-room subject (OJT, Practicum, etc.)
    public $duration_hours = 3;
    public $meetings_per_week = 1;

    // CSV Import Logic
    public $importFile;
    public $previewData = [];
    public $selectedSubjects = [];
    public $selectAll = false;
    public $showDuplicateConfirmModal = false;
    public $duplicateCandidateId = null;
    public bool $showProtectedDeleteModal = false;
    public bool $protectedDeleteSecondStep = false;
    public ?int $protectedDeleteSubjectId = null;
    public array $protectedDeleteImpact = [];

    // Major mapping by department
    private $majorsByDept = [
        'CCS'  => ['IT' => 'Information Technology', 'ACT' => 'Assistive Computer Technology'],
        'SHTM' => ['HM' => 'Hospitality Management', 'TM' => 'Tourism Management'],
        'COC'  => ['FB' => 'Forensic Biology', 'LD' => 'Lie Detection', 'QD' => 'Questioned Documents'],
        'CTE'  => ['ED' => 'Education'],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedDept($value) { $this->selectedMajor = ''; $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedMajor() { $this->resetPage(); }
    public function updatedSelectedSection() { $this->resetPage(); }
    public function updatedCatalogMode() { $this->selectedArchiveBatch = ''; $this->selectedSubjects = []; $this->selectAll = false; $this->resetPage(); }
    public function updatedSelectedArchiveBatch() { $this->selectedSubjects = []; $this->selectAll = false; $this->resetPage(); }

    // ============================================================
    // ROOM OVERRIDE SYNC
    // ============================================================

    /**
     * Single checkbox that means different things depending on subject type:
     *   Major  + checked → preferred_room_type = 'LECTURE', requires_lab = false
     *   Major  + unchecked → preferred_room_type = '',       requires_lab = false  (auto/lab)
     *   Minor  + checked → preferred_room_type = 'LAB',     requires_lab = true   (dept lab)
     *   Minor  + unchecked → preferred_room_type = '',       requires_lab = false  (auto/lecture)
     *
     * Mutual exclusion: checking Room Override automatically unchecks Practicum / OJT.
     */
    public function updatedRoomOverride(bool $value): void
    {
        if ($value) {
            // Uncheck Practicum when Room Override is activated — they are mutually exclusive.
            $this->is_practicum = false;
        }
        $this->syncRoomFieldsFromOverride($value);
    }

    /**
     * Mutual exclusion: checking Practicum / OJT automatically unchecks Room Override
     * and resets the derived room fields to their auto/default state.
     */
    public function updatedIsPracticum(bool $value): void
    {
        if ($value) {
            // Uncheck Room Override and reset all derived room fields to auto (no override).
            $this->room_override       = false;
            $this->preferred_room_type = '';
            $this->requires_lab        = false;
        }
    }

    /**
     * When the subject type changes, re-evaluate the override state so the
     * stored preferred_room_type always matches the current type + checkbox combination.
     */
    public function updatedType(string $value): void
    {
        $this->syncRoomFieldsFromOverride($this->room_override);
    }

    private function syncRoomFieldsFromOverride(bool $override): void
    {
        $isMajor = strtolower($this->type ?? 'major') === 'major';

        if ($isMajor) {
            // Major: override = use lecture instead of lab
            $this->preferred_room_type = $override ? 'LECTURE' : '';
            $this->requires_lab        = false;
        } else {
            // Minor: override = use dept lab instead of lecture
            $this->preferred_room_type = $override ? 'LAB' : '';
            $this->requires_lab        = $override;
        }
    }

    /**
     * Kept for backward-compatibility — the old dropdown may still write to this.
     * We derive room_override from the stored preferred_room_type when loading.
     */
    public function updatedPreferredRoomType(string $value): void
    {
        $this->requires_lab = str_contains(strtoupper($value), 'LAB');
    }

    private function activePeriod(): array
    {
        return Setting::getAcademicPeriod();
    }

    private function activeSubjectsQuery()
    {
        $period = $this->activePeriod();
        return Subject::activeTerm($period['semester'], $period['school_year']);
    }

    private function archiveOptions()
    {
        if (Schema::hasTable('schedule_archives')) {
            return DB::table('schedule_archives')
                ->whereNotNull('archive_batch_id')
                ->select('archive_batch_id', 'semester', 'semester_name', 'school_year', 'total_subjects', 'archived_at')
                ->latest('archived_at')
                ->get();
        }

        return Subject::archived()
            ->whereNotNull('archive_batch')
            ->select('archive_batch', 'semester', 'academic_year')
            ->distinct()
            ->orderByDesc('archive_batch')
            ->get()
            ->map(fn ($archive) => (object) [
                'archive_batch_id' => $archive->archive_batch,
                'semester'         => $archive->semester,
                'semester_name'    => Setting::semesterDisplayName($archive->semester, $archive->academic_year),
                'school_year'      => $archive->academic_year,
                'total_subjects'   => null,
                'archived_at'      => null,
            ]);
    }

    // ============================================================
    // AVAILABLE MAJORS
    // ============================================================

    public function getAvailableMajorsProperty()
    {
        if (empty($this->department)) {
            return [];
        }
        return $this->majorsByDept[strtoupper($this->department)] ?? [];
    }

    // ============================================================
    // EDP CODE GENERATION
    // ============================================================

    public function updatedMajor($value)
    {
        if (! empty($value) && ! empty($this->year_level)) {
            $this->generateEdpCode();
        }
    }

    public function updatedYearLevel($value)
    {
        if (! empty($value) && ! empty($this->major)) {
            $this->generateEdpCode();
        }
    }

    private function generateEdpCode(): void
    {
        if (empty($this->major) || empty($this->year_level) || empty($this->department)) {
            $this->edp_code = '';
            return;
        }
        $period = $this->activePeriod();
        $this->edp_code = Subject::generateEdpCode(
            strtoupper($this->major),
            (int) $this->year_level,
            $period['school_year'],
            $period['semester'],
            $this->subjectId ? (int) $this->subjectId : null
        );
    }

    public function getSubjectCodeDuplicateProperty()
    {
        if (empty($this->subject_code) || empty($this->major) || empty($this->year_level) || empty($this->section)) {
            return false;
        }
        $query = $this->activeSubjectsQuery()
            ->where('subject_code', strtoupper($this->subject_code))
            ->where('major',        strtoupper($this->major))
            ->where('year_level',   (int) $this->year_level)
            ->where('section',      strtoupper($this->section));
        if ($this->isEditMode && $this->subjectId) {
            $query->where('id', '!=', $this->subjectId);
        }
        return $query->exists();
    }

    private function normalizeYearLevel(mixed $raw): int
    {
        $edpService = app(\App\Services\EdpCodeService::class);
        return $edpService->yearLevelDigit($raw ?? 1);
    }

    private function validateDepartmentAccess($dept): bool
    {
        $user      = auth()->user();
        $userRole  = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        if (in_array($userRole, $powerRoles)) {
            return true;
        }
        if (in_array($userRole, ['dean', 'oic'])) {
            return strtoupper($dept) === strtoupper($user->department);
        }
        return false;
    }

    /**
     * Check whether the current user can access Practicum / OJT subjects.
     *
     * Practicum subjects are always Major-type (off-campus deployments), so they
     * fall under the same access tier as Major subjects. Associate Deans are
     * explicitly excluded — they may manage Minor/elective subjects only.
     *
     * Allowed roles: admin, registrar, dean, oic
     * Blocked roles: associate_dean (and any other unlisted role)
     *
     * Dean / OIC are further restricted to their own department (enforced
     * separately by validateDepartmentAccess()).
     */
    private function canAccessPracticum(): bool
    {
        $role = strtolower(auth()->user()->role ?? '');
        return in_array($role, ['admin', 'registrar', 'dean', 'oic']);
    }

    // ============================================================
    // CSV IMPORT
    // ============================================================

    public function updatedImportFile()
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);
        usleep(500000);
        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headerRow = array_shift($data);
        $normalizedHeader = array_map(fn ($h) => strtolower(trim($h)), $headerRow);

        if (in_array('room_name', $normalizedHeader) || in_array('building', $normalizedHeader)) {
            $this->abortImport('Room Registry'); return;
        }
        if (in_array('faculty_name', $normalizedHeader) || in_array('employee_id', $normalizedHeader)) {
            $this->abortImport('Faculty Directory'); return;
        }

        $required    = ['edp_code', 'subject_code', 'section', 'description', 'major', 'year_level', 'units', 'department', 'duration_hours', 'meetings_per_week'];
        $missing     = array_diff($required, $normalizedHeader);
        $hasSubjectType = in_array('type', $normalizedHeader, true) || in_array('subject_type', $normalizedHeader, true);

        if ($missing || ! $hasSubjectType) {
            $this->abortImport('Invalid Subject Template'); return;
        }

        $indexes    = array_flip($normalizedHeader);
        $period     = $this->activePeriod();
        $edpService = app(EdpCodeService::class);

        $this->previewData = collect(array_slice($data, 0, 10))
            ->map(function ($row, $index) use ($indexes, $period, $edpService) {
                $edp    = strtoupper(trim($row[$indexes['edp_code']] ?? ''));
                $exists = $edp !== '' && Subject::edpExistsInWorkspace($edp, $period['school_year'], $period['semester']);
                $formatLabel = $edp !== '' ? $edpService->formatLabel($edp) : 'empty';
                $semesterMismatch = $formatLabel === 'new'
                    && ! $edpService->validateSemesterMatch($edp, $period['semester']);
                return [
                    'row'               => $index + 2,
                    'edp_code'          => $edp,
                    'subject'           => $row[$indexes['subject_code']] ?? '',
                    'exists'            => $exists,
                    'format_label'      => $formatLabel,
                    'semester_mismatch' => $semesterMismatch,
                ];
            })
            ->toArray();
    }

    private function abortImport($detectedType): void
    {
        $this->reset(['importFile', 'previewData']);
        $this->dispatch('toast', [
            'type'    => 'warning',
            'message' => 'Incorrect CSV Detected',
            'detail'  => "This file appears to be for the {$detectedType}. Please upload the Subject CSV template instead.",
        ]);
    }

    public function importSubjects()
    {
        if (! $this->importFile) { return; }

        $path   = $this->importFile->getRealPath();
        $file   = fopen($path, 'r');
        $header = true;
        $indexes = [];
        $count        = 0;
        $skipped      = 0;
        $formatErrors   = [];
        $semesterErrors = [];
        $detectedDept = null;
        $actor        = auth()->user();
        $userRole    = strtolower($actor->role);
        $powerRoles  = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);
        $edpService = app(EdpCodeService::class);
        $period     = $this->activePeriod();
        $rowNumber  = 1;

        if (empty($period['semester']) || empty($period['school_year'])) {
            fclose($file);
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No Active Workspace', 'detail' => 'No active semester workspace found.']);
            return;
        }

        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            if ($header) {
                $indexes = array_flip(array_map(fn ($h) => strtolower(trim($h)), $row));
                $header  = false;
                continue;
            }

            $rowNumber++;
            $value = fn (string $key, $default = '') => trim((string) ($row[$indexes[$key] ?? -1] ?? $default));
            $typeColumn = array_key_exists('subject_type', $indexes) ? 'subject_type' : 'type';

            if ($value('edp_code') === '') { $skipped++; continue; }

            $edpCode  = strtoupper($value('edp_code'));
            $rowDept  = strtoupper($value('department'));

            if (! $edpService->isValidForCreation($edpCode)) {
                $formatErrors[] = $edpService->validationMessage($edpCode, $rowNumber);
                $skipped++;
                continue;
            }
            if (! $edpService->validateSemesterMatch($edpCode, $period['semester'])) {
                $semesterErrors[] = $edpService->semesterMismatchMessage($edpCode, $period['semester'], $rowNumber);
                $skipped++;
                continue;
            }
            if (! $this->validateDepartmentAccess($rowDept)) { $skipped++; continue; }
            if (! $detectedDept && ! empty($rowDept)) { $detectedDept = $rowDept; }
            $exists  = Subject::edpExistsInWorkspace($edpCode, $period['school_year'], $period['semester']);
            if ($exists) { \Log::warning("CSV import row {$rowNumber}: {$edpCode} already exists."); $skipped++; continue; }

            $rowMajor      = strtoupper($value('major'));
            $rowYearLevel  = $this->normalizeYearLevel($value('year_level', 1));
            $rawDuration   = $value('duration_hours', '3');
            $rawType       = $value($typeColumn, 'Major');
            $rawMeetings   = (int) $value('meetings_per_week', 1);
            $rawUnits      = (int) $value('units', 3);
            $clampedUnits  = max(3, min(5, $rawUnits));
            $section       = strtoupper($value('section', 'A'));
            $normalizedType = str_contains(strtolower($rawType), 'minor') ? 'Minor' : 'Major';
            $specialization = strtoupper($value('specialization', $rowMajor));

            // preferred_room_type is source of truth; derive requires_lab from it.
            // Empty string = auto (no override) — the auto-scheduler uses its own
            // type/major heuristics. Only write 'LAB' when the subject is explicitly
            // a lab subject; never write 'LECTURE' as a default because that would
            // incorrectly mark the room_override checkbox as checked on first edit.
            $rawPreferredRoomType = strtoupper($value('preferred_room_type', ''));
            if ($rawPreferredRoomType === '') {
                $heuristicLab = filter_var($value('requires_lab', false), FILTER_VALIDATE_BOOLEAN)
                    || str_contains(strtoupper($rawType . ' ' . $value('description') . ' ' . $specialization), 'LAB');
                $rawPreferredRoomType = $heuristicLab ? 'LAB' : ''; // '' = auto, not an override
            }
            $requiresLab = str_contains($rawPreferredRoomType, 'LAB');

            Subject::create([
                'edp_code'          => $edpCode,
                'subject_code'      => strtoupper($value('subject_code')),
                'section'           => $section,
                'description'       => $value('description'),
                'major'             => $rowMajor,
                'year_level'        => $rowYearLevel,
                'units'             => $clampedUnits,
                'department'        => $rowDept,
                'duration_hours'    => (float) filter_var($rawDuration, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 3,
                'meetings_per_week' => $rawMeetings,
                'type'              => $normalizedType,
                'subject_type'      => $rawType,
                'requires_lab'      => $requiresLab,
                'preferred_room_type' => $rawPreferredRoomType,
                'specialization'    => $specialization,
                'semester'          => $period['semester'],
                'school_year'       => $period['school_year'],
                'academic_year'     => $period['school_year'],
                'workspace_key'     => $period['workspace_key'],
                'is_archived'       => false,
            ]);
            $count++;
        }
        fclose($file);

        if (! empty($semesterErrors)) {
            $semSample = array_slice($semesterErrors, 0, 3);
            $more = count($semesterErrors) > 3 ? (' … and ' . (count($semesterErrors) - 3) . ' more.') : '';
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Wrong Semester in EDP Code', 'detail' => implode(' | ', $semSample) . $more]);
        }
        if (! empty($formatErrors)) {
            $errorSample = array_slice($formatErrors, 0, 5);
            $more = count($formatErrors) > 5 ? (' … and ' . (count($formatErrors) - 5) . ' more.') : '';
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid EDP Code Format', 'detail' => implode(' | ', $errorSample) . $more]);
        }

        if ($count > 0) {
            $targetDept = ! empty($this->selectedDept) ? $this->selectedDept : ($detectedDept ?? $actor->department ?? 'General');
            Activity::create(['user_id' => $actor->id, 'action' => 'Import', 'module' => 'Subjects', 'description' => "Successfully batch-imported {$count} subjects into the {$targetDept} department." . ($skipped > 0 ? " ({$skipped} rows skipped.)" : ''),]);
            $recipients = User::where('id', '!=', $actor->id)->where(function ($query) use ($targetDept) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($targetDept) { $q->whereIn('role', ['dean', 'oic'])->where('department', $targetDept); }); })->get();
            if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification((object) ['subject_code' => 'BATCH IMPORT', 'subject_description' => "{$count} Subjects synchronized for {$targetDept}"], 'subject_imported')); }
            $this->dispatch('notify', ['type' => 'success', 'title' => 'CATALOG SYNCED', 'message' => "Successfully batch-imported {$count} subjects." . ($skipped > 0 ? " ({$skipped} skipped.)" : ''), 'sender_name' => $actor->name]);
            $this->dispatch('subjectUpdated');
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Import Complete', 'detail' => "{$count} subjects added for {$targetDept}." . ($skipped > 0 ? " {$skipped} rows were skipped." : '')]);
        } else {
            $allRejected = ! empty($formatErrors) || ! empty($semesterErrors);
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Import Failed', 'detail' => ! $allRejected ? 'No valid subjects found or unauthorized department access.' : 'All rows were rejected. Check EDP codes format and semester digit.']);
        }

        $this->reset(['importFile', 'bulkOpen', 'previewData']);
    }

    // ============================================================
    // MODAL MANAGEMENT
    // ============================================================

    public function openModal()
    {
        if ($this->catalogMode !== 'active') {
            $this->catalogMode = 'active';
            $this->selectedArchiveBatch = '';
        }
        $this->resetValidation();
        $this->resetExcept(['selectedDept', 'search', 'selectedYear', 'selectedMajor', 'selectedSection']);
        $this->isEditMode       = false;
        $this->units            = 3;
        $this->type             = 'Major';
        $this->requires_lab     = false;
        $this->preferred_room_type = '';
        $this->duration_hours   = 3;
        $this->meetings_per_week = 1;
        $this->is_practicum     = false;
        $this->major            = '';
        $this->year_level       = '';
        $this->edp_code         = '';
        $this->subject_code     = '';
        $this->section          = '';
        $user     = auth()->user();
        $userRole = strtolower($user->role);
        $this->department = in_array($userRole, ['admin', 'registrar', 'associate_dean']) ? '' : $user->department;
        $this->showModal = true;
    }

    public function editSubject($id)
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Switch back to the current semester before editing subjects.']);
            return;
        }

        // Clear stale validation errors AND room-related state before loading
        // fresh data. Without this reset, a previous modal session's stale
        // room_override / preferred_room_type can bleed into the new load.
        $this->resetValidation();
        $this->reset([
            'room_override', 'requires_lab', 'preferred_room_type',
            'type', 'description', 'units', 'duration_hours', 'meetings_per_week',
            'is_practicum',
        ]);

        $subject = $this->activeSubjectsQuery()->findOrFail($id);

        if (! $this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to edit subjects in this department.']);
            return;
        }

        // Associate Deans are not permitted to access Practicum / OJT subjects.
        if ($subject->is_practicum && ! $this->canAccessPracticum()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'Practicum / OJT subjects are managed by Admin, Registrar, Dean, or OIC only.']);
            return;
        }

        // ── Restore room override checkbox ──────────────────────────────────────
        // preferred_room_type is the single source of truth written by executeSave():
        //   Major + checkbox checked   → saved as 'LECTURE'
        //   Minor + checkbox checked   → saved as 'LAB'
        //   Either + unchecked          → saved as '' (empty string / auto)
        // We read it back directly — no guessing, no heuristics.
        $subjectIsMajor = strtolower($subject->type ?? 'major') === 'major';
        $savedRoomType  = strtoupper(trim((string) ($subject->preferred_room_type ?? '')));

        // Checked only when the exact override value is stored.
        // Empty string / null / any other legacy value → unchecked (false).
        $roomOverride = $subjectIsMajor
            ? ($savedRoomType === 'LECTURE')
            : ($savedRoomType === 'LAB');

        // ── Assign all properties in one clean block ────────────────────────────
        $this->isEditMode          = true;
        $this->subjectId           = (int) $subject->id;
        $this->edp_code            = $subject->edp_code;
        $this->subject_code        = $subject->subject_code;
        $this->section             = $subject->section;
        $this->description         = $subject->description;
        $this->units               = (int) $subject->units;
        $this->type                = $subject->type ?? 'Major';
        $this->requires_lab        = ($savedRoomType === 'LAB');
        $this->preferred_room_type = $savedRoomType;       // always the uppercased canonical value
        $this->room_override       = (bool) $roomOverride; // explicit bool cast — no ambiguity
        $this->duration_hours      = $subject->duration_hours ?? 3;
        $this->meetings_per_week   = (int) ($subject->meetings_per_week ?? 1);
        $this->major               = $subject->major ?? '';
        $this->year_level          = (int) ($subject->year_level ?? 1);
        $this->department          = $subject->department;
        $this->is_practicum        = (bool) ($subject->is_practicum ?? false);

        $this->showModal = true;
    }

    public function updatedUnits($value)
    {
        if (! is_numeric($value) || $value < 3) {
            $this->units = 3;
        } elseif ($value > 5) {
            $this->units = 5;
        }
    }

    // ============================================================
    // SAVE SUBJECT (Create / Update)
    // ============================================================

    public function saveSubject()
    {
        if ($this->catalogMode !== 'active') {
            $this->addError('edp_code', 'Archived subjects are read only.');
            return;
        }
        $user        = auth()->user();
        $userRole    = strtolower($user->role ?? '');
        $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);
        if (! $isPowerUser && ! $this->validateDepartmentAccess($this->department)) {
            $this->addError('department', 'You do not have permission to manage subjects in this department.');
            return;
        }

        // Associate Deans cannot create or update Practicum / OJT subjects.
        if ($this->is_practicum && ! $this->canAccessPracticum()) {
            $this->addError('is_practicum', 'Practicum / OJT subjects can only be managed by Admin, Registrar, Dean, or OIC.');
            return;
        }
        $edpUpper         = strtoupper(trim($this->edp_code));
        $sectionUpper     = strtoupper($this->section);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper       = strtoupper($this->major);
        $period           = $this->activePeriod();

        $this->validate([
            'edp_code'          => ['required', 'regex:/^[A-Z]{2,4}-\d{7}$/', Rule::unique('subjects', 'edp_code')->where(fn ($query) => $query->where('school_year', $period['school_year'])->where('semester', $period['semester']))->ignore($this->subjectId)],
            'subject_code'      => 'required',
            'section'           => 'required|max:10',
            'department'        => 'required',
            'major'             => 'required',
            'year_level'        => 'required|integer|min:1|max:4',
            'description'       => 'required',
            'units'             => 'required|integer|min:3|max:5',
            'type'              => 'required|in:Major,Minor',
            'requires_lab'      => 'boolean',
            'preferred_room_type' => 'nullable|string|max:80',
            'duration_hours'    => 'required|numeric|min:1|max:10',
            'meetings_per_week' => 'required|integer|min:1|max:5',
        ], [
            'edp_code.required' => 'The EDP code is required.',
            'edp_code.regex'    => 'Invalid EDP code format. Required: [MAJOR]-[YY][SEM][LEVEL][SEQ] — e.g. IT-2611001.',
            'edp_code.unique'   => "EDP code \"{$edpUpper}\" already exists in {$period['semester']} {$period['school_year']}.",
        ]);

        $edpService = app(EdpCodeService::class);
        // EDP format / semester-match validation is only meaningful when CREATING.
        // In edit mode the code is readonly and was already validated at creation time;
        // re-running these checks would silently block saves (e.g. after a semester switch).
        if (! $this->isEditMode) {
            if (! $edpService->isNew($edpUpper)) {
                $this->addError('edp_code', $edpService->validationMessage($edpUpper));
                return;
            }
            if (! $edpService->validateSemesterMatch($edpUpper, $period['semester'])) {
                $this->addError('edp_code', $edpService->semesterMismatchMessage($edpUpper, $period['semester']));
                return;
            }
        }
        if (! $this->isEditMode && $this->getSubjectCodeDuplicateProperty()) {
            $this->addError('subject_code', "Subject code '{$subjectCodeUpper}' already exists in Section {$sectionUpper} for {$majorUpper} - Year {$this->year_level}.");
            return;
        }
        if (! $this->isEditMode && Subject::edpExistsInWorkspace($edpUpper, $period['school_year'], $period['semester'])) {
            $this->addError('edp_code', "EDP code '{$edpUpper}' already exists in {$period['semester']} {$period['school_year']}.");
            return;
        }

        $this->executeSave();
    }

    public function executeSave()
    {
        $user             = auth()->user();
        $deptUpper        = strtoupper($this->department);
        $edpUpper         = strtoupper($this->edp_code);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper       = strtoupper($this->major);
        $period           = $this->activePeriod();
        $normalizedType   = in_array($this->type, ['Major', 'Minor']) ? $this->type : 'Major';
        $isMajor          = $normalizedType === 'Major';

        // ── Room type resolution from the single checkbox ──────────────────
        // room_override=true on Major  → force LECTURE (bypass lab routing)
        // room_override=true on Minor  → force LAB     (use dept lab)
        // room_override=false on either → auto ('')    (scheduler decides per default logic)
        if ($this->room_override) {
            $resolvedRoomType    = $isMajor ? 'LECTURE' : 'LAB';
            $resolvedRequiresLab = !$isMajor; // true only when minor forced to lab
        } else {
            $resolvedRoomType    = '';   // empty = auto; scheduler uses type/major heuristics
            $resolvedRequiresLab = false;
        }

        $subject = Subject::updateOrCreate(
            ['id' => $this->subjectId],
            [
                'edp_code'            => $edpUpper,
                'subject_code'        => $subjectCodeUpper,
                'section'             => strtoupper($this->section),
                'description'         => $this->description,
                'major'               => $majorUpper,
                'year_level'          => (int) $this->year_level,
                'units'               => (int) $this->units,
                'type'                => $normalizedType,
                'subject_type'        => $normalizedType,
                'requires_lab'        => $resolvedRequiresLab,
                'preferred_room_type' => $resolvedRoomType,
                'specialization'      => $majorUpper,
                'duration_hours'      => (float) $this->duration_hours,
                'meetings_per_week'   => (int) $this->meetings_per_week,
                'department'          => $deptUpper,
                'is_practicum'        => (bool) $this->is_practicum,
                'semester'            => $period['semester'],
                'school_year'         => $period['school_year'],
                'academic_year'       => $period['school_year'],
                'workspace_key'       => $period['workspace_key'],
                'is_archived'         => false,
                'archived_at'         => null,
                'archive_batch'       => null,
            ]
        );

        // ── Practicum auto-finalize ────────────────────────────────────────────
        // Practicum / OJT subjects need no room, time, or faculty. As soon as one
        // is created (or re-saved as practicum), create a STATUS_FINALIZED placeholder
        // schedule row so it appears immediately in the block schedule with the
        // correct "Auto-Finalized" status instead of "NOT SCHEDULED".
        if ($subject->is_practicum) {
            $existingSchedule = \App\Models\Schedule::activeTerm($period['semester'], $period['school_year'])
                ->where('subject_id', $subject->id)
                ->first();

            if (! $existingSchedule) {
                \App\Models\Schedule::create([
                    'subject_id'    => $subject->id,
                    'section'       => $subject->section,
                    'day'           => null,
                    'start_time'    => null,
                    'end_time'      => null,
                    'room_id'       => null,
                    'faculty_id'    => null,
                    'status'        => \App\Models\Schedule::STATUS_FINALIZED,
                    'semester'      => $period['semester'],
                    'school_year'   => $period['school_year'],
                    'academic_year' => $period['school_year'],
                    'workspace_key' => $period['workspace_key'],
                    'edp_code'      => $subject->edp_code,
                ]);
            } elseif ($existingSchedule->status !== \App\Models\Schedule::STATUS_FINALIZED) {
                // Subject was previously a regular subject and is being converted to practicum.
                $existingSchedule->update([
                    'status'     => \App\Models\Schedule::STATUS_FINALIZED,
                    'day'        => null,
                    'start_time' => null,
                    'end_time'   => null,
                    'room_id'    => null,
                ]);
            }
        }

        $this->logActivityAndNotify($subject, $user, $deptUpper);
        $this->showDuplicateConfirmModal = false;
        $this->showModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $this->isEditMode ? 'Subject Updated' : 'Subject Created', 'detail' => "{$subject->subject_code} is now synchronized."]);
        $this->dispatch('subjectUpdated');
        $this->completeFormReset();
    }

    private function completeFormReset(): void
    {
        $this->reset(['edp_code', 'subject_code', 'section', 'description', 'units', 'type', 'duration_hours', 'major', 'year_level', 'department', 'subjectId', 'isEditMode', 'meetings_per_week', 'requires_lab', 'preferred_room_type', 'room_override', 'is_practicum']);
    }

    private function logActivityAndNotify($subject, $user, $deptUpper): void
    {
        Activity::create(['user_id' => $user->id, 'action' => $this->isEditMode ? 'Update' : 'Add', 'module' => 'Subjects', 'description' => $this->isEditMode ? "Updated {$subject->subject_code} in {$deptUpper}." : "Manually added {$subject->subject_code} to {$deptUpper}."]);
        $recipients = User::where('id', '!=', $user->id)->where(function ($query) use ($deptUpper) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($deptUpper) { $q->whereIn('role', ['dean', 'oic'])->where('department', $deptUpper); }); })->get();
        if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification($subject, $this->isEditMode ? 'updated' : 'created')); }
    }

    // ============================================================
    // BULK OPERATIONS
    // ============================================================

    public function bulkDuplicate()
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Switch back to the current semester before duplicating subjects.']);
            return;
        }
        $count = count($this->selectedSubjects);
        if ($count === 0) return;
        $actor           = auth()->user();
        $duplicatedCount = 0;
        $skippedCount    = 0;
        $skippedReasons  = [];
        $period          = $this->activePeriod();

        foreach ($this->selectedSubjects as $id) {
            $original = $this->activeSubjectsQuery()->find($id);
            if (! $original) continue;
            if (! $this->validateDepartmentAccess($original->department)) { $skippedCount++; continue; }
            $currentSection = strtoupper($original->section ?: 'A');
            $nextSection    = $currentSection === 'Z' ? 'AA' : ($currentSection === 'AA' ? 'AB' : chr(ord($currentSection) + 1));
            $newEdp = Subject::generateEdpCode($original->major ?: strtok((string) $original->edp_code, '-'), (int) $original->year_level, $period['school_year'], $period['semester']);
            $subjectExistsInNextSection = $this->activeSubjectsQuery()->where('subject_code', strtoupper($original->subject_code))->where('section', $nextSection)->where('major', $original->major)->where('year_level', $original->year_level)->where('department', $original->department)->exists();
            if ($subjectExistsInNextSection) { $skippedCount++; $skippedReasons[] = "{$original->subject_code} in Section {$nextSection} already exists"; continue; }
            if (Subject::edpExistsInWorkspace($newEdp, $period['school_year'], $period['semester'])) { $skippedCount++; continue; }

            // Carry over room type consistently — preferred_room_type is source of truth
            $origRoomType   = $original->preferred_room_type ?: 'LECTURE';
            $origRequiresLab = str_contains(strtoupper($origRoomType), 'LAB');

            try {
                Subject::create([
                    'edp_code'           => $newEdp,
                    'subject_code'       => $original->subject_code,
                    'section'            => $nextSection,
                    'description'        => $original->description,
                    'major'              => $original->major,
                    'year_level'         => $original->year_level,
                    'units'              => $original->units,
                    'department'         => $original->department,
                    'type'               => $original->type ?? 'Major',
                    'subject_type'       => $original->subject_type,
                    'requires_lab'       => $origRequiresLab,
                    'preferred_room_type' => $origRoomType,
                    'specialization'     => $original->specialization,
                    'duration_hours'     => $original->duration_hours,
                    'meetings_per_week'  => $original->meetings_per_week ?? 1,
                    'semester'           => $period['semester'],
                    'school_year'        => $period['school_year'],
                    'academic_year'      => $period['school_year'],
                    'workspace_key'      => $period['workspace_key'],
                    'is_archived'        => false,
                ]);
                $duplicatedCount++;
            } catch (\Exception $e) { \Log::error("Error duplicating subject {$original->id}: " . $e->getMessage()); $skippedCount++; }
        }

        Activity::create(['user_id' => $actor->id, 'action' => 'Bulk Duplicate', 'module' => 'Subjects', 'description' => "Created {$duplicatedCount} new subject sections via bulk duplication." . ($skippedCount > 0 ? " ({$skippedCount} skipped)" : '')]);
        $this->reset(['selectedSubjects', 'selectAll']);
        $this->dispatch('subjectUpdated');

        if ($skippedCount > 0) {
            $detail = "Duplicated {$duplicatedCount} subjects, {$skippedCount} skipped.";
            if (! empty($skippedReasons)) { $detail .= ' Reasons: ' . implode(', ', array_slice($skippedReasons, 0, 3)); if (count($skippedReasons) > 3) { $detail .= ', and more…'; } }
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Bulk Duplicate Partial', 'detail' => $detail]);
        } else {
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Bulk Duplicate Complete', 'detail' => "{$duplicatedCount} subjects have been copied to the next section."]);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($this->catalogMode !== 'active') { $this->selectAll = false; $this->selectedSubjects = []; return; }
        if ($value) {
            $user        = auth()->user();
            $userRole    = strtolower($user->role ?? '');
            $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);
            $query = $this->activeSubjectsQuery();
            if (! $isPowerUser) { $query->where('department', $user->department); } elseif (! empty($this->selectedDept)) { $query->where('department', $this->selectedDept); }
            if (! empty($this->selectedSection)) { $query->where('section', $this->selectedSection); }
            if (! empty($this->search)) { $query->where(function ($q) { $q->where('subject_code', 'like', "%{$this->search}%")->orWhere('edp_code', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%"); }); }
            if (! empty($this->selectedYear)) { $query->where('year_level', $this->selectedYear); }
            if (! empty($this->selectedMajor)) { $query->where('major', strtoupper($this->selectedMajor)); }
            $this->selectedSubjects = $query->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedSubjects = [];
        }
    }

    public function deleteSelected()
    {
        if ($this->catalogMode !== 'active') { $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Switch back to the current semester before editing subjects.']); return; }
        $count = count($this->selectedSubjects);
        $user  = auth()->user();
        if ($count > 0) {
            $sampleSubject = $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->first();
            $targetDept    = $sampleSubject ? $sampleSubject->department : ($user->department ?? 'General');
            if (! $this->validateDepartmentAccess($targetDept)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to delete subjects in this department.']); return; }
            $protectedCount = Schedule::activeTerm()->whereIn('subject_id', $this->selectedSubjects)->where('status', Schedule::STATUS_FINALIZED)->distinct('subject_id')->count('subject_id');
            if ($protectedCount > 0) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Finalized Subjects Protected', 'detail' => "{$protectedCount} selected subject(s) are finalized. Delete them individually to complete the double confirmation."]); return; }
            $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->delete();
            Activity::create(['user_id' => $user->id, 'action' => 'Delete', 'module' => 'Subjects', 'description' => "Bulk removed {$count} subjects from the {$targetDept} catalog."]);
            $recipients = User::where('id', '!=', $user->id)->where(function ($query) use ($targetDept) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($targetDept) { $q->whereIn('role', ['dean', 'oic'])->where('department', $targetDept); }); })->get();
            if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification((object) ['subject_code' => 'BATCH PURGE', 'subject_description' => "{$count} Records removed from {$targetDept}"], 'deleted')); }
            $this->dispatch('notify', ['type' => 'error', 'title' => 'REGISTRY PURGED', 'message' => "Successfully removed {$count} subjects from the database.", 'sender_name' => $user->name]);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Batch Deleted', 'detail' => "{$count} subjects successfully removed."]);
            $this->dispatch('subjectUpdated');
            $this->reset(['selectedSubjects', 'selectAll']);
        }
    }

    public function confirmDeleteSubject($id): void
    {
        if ($this->catalogMode !== 'active') { $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Archived subjects cannot be edited or deleted from this view.']); return; }
        $subject = $this->activeSubjectsQuery()->findOrFail($id);
        if (! $this->validateDepartmentAccess($subject->department)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to delete subjects in this department.']); return; }
        $finalizedSchedules = Schedule::activeTerm()->where('subject_id', $subject->id)->where('status', Schedule::STATUS_FINALIZED)->with(['room:id,room_name', 'faculty:id,full_name'])->get();
        if ($finalizedSchedules->isEmpty()) { $this->deleteSubject($id, true); return; }
        $this->protectedDeleteSubjectId = $subject->id;
        $this->protectedDeleteSecondStep = false;
        $this->protectedDeleteImpact = ['subject_code' => $subject->subject_code, 'description' => $subject->description, 'count' => $finalizedSchedules->count(), 'schedules' => $finalizedSchedules->take(5)->map(fn (Schedule $schedule) => ['day' => $schedule->day, 'time' => $schedule->time_display, 'room' => $schedule->room?->room_name ?? 'Unassigned', 'faculty' => $schedule->faculty?->full_name ?? 'Unassigned'])->all()];
        $this->showProtectedDeleteModal = true;
    }

    public function advanceProtectedDeleteConfirmation(): void { $this->protectedDeleteSecondStep = true; }
    public function cancelProtectedDelete(): void { $this->showProtectedDeleteModal = false; $this->protectedDeleteSecondStep = false; $this->protectedDeleteSubjectId = null; $this->protectedDeleteImpact = []; }
    public function deleteProtectedSubject(): void { if (! $this->protectedDeleteSubjectId || ! $this->protectedDeleteSecondStep) { return; } $id = $this->protectedDeleteSubjectId; $this->cancelProtectedDelete(); $this->deleteSubject($id, true); }

    public function deleteSubject($id, bool $confirmedProtected = false)
    {
        if ($this->catalogMode !== 'active') { $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Archived subjects cannot be edited or deleted from this view.']); return; }
        $user    = auth()->user();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);
        if (! $this->validateDepartmentAccess($subject->department)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to delete subjects in this department.']); return; }
        $hasFinalizedSchedule = Schedule::activeTerm()->where('subject_id', $subject->id)->where('status', Schedule::STATUS_FINALIZED)->exists();
        if ($hasFinalizedSchedule && ! $confirmedProtected) { $this->confirmDeleteSubject($id); return; }
        $subjectCode = $subject->subject_code;
        $subjectDesc = $subject->description;
        $targetDept  = $subject->department;
        $recipients = User::where('id', '!=', $user->id)->where(function ($query) use ($targetDept) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($targetDept) { $q->whereIn('role', ['dean', 'oic'])->where('department', $targetDept); }); })->get();
        if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification($subject, 'deleted')); }
        $subject->delete();
        Activity::create(['user_id' => $user->id, 'action' => 'Delete', 'module' => 'Subjects', 'description' => "Manually removed subject {$subjectCode} from the {$targetDept} catalog."]);
        $this->dispatch('notify', ['type' => 'error', 'title' => 'SUBJECT REMOVED', 'message' => "{$subjectCode} has been deleted from the registry.", 'sender_name' => $user->name]);
        $this->dispatch('toast', ['type' => 'warning', 'message' => 'Subject Deleted', 'detail' => "{$subjectCode} - {$subjectDesc} removed."]);
        $this->dispatch('subjectUpdated');
    }

    // ============================================================
    // LIFECYCLE
    // ============================================================

    public function mount()
    {
        $user     = auth()->user();
        $userRole = strtolower($user->role);
        $this->selectedDept = in_array($userRole, ['admin', 'registrar', 'associate_dean']) ? '' : $user->department;
    }

    public function render()
    {
        $user        = auth()->user();
        $userRole    = strtolower($user->role ?? '');
        $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);
        $period         = $this->activePeriod();
        $archiveOptions = $this->archiveOptions();
        $isArchiveMode  = $this->catalogMode === 'archive';

        $query = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') { $query->whereRaw('1 = 0'); }
        if (! $isPowerUser) { $query->where('department', $user->department); } elseif (! empty($this->selectedDept)) { $query->where('department', $this->selectedDept); }
        if (! empty($this->selectedSection)) { $query->where('section', $this->selectedSection); }
        if (! empty($this->selectedYear)) { $query->where('year_level', (int) $this->selectedYear); }
        if (! empty($this->selectedMajor)) { $query->where('major', strtoupper($this->selectedMajor)); }
        if (! empty($this->search)) { $query->where(function ($q) { $q->where('subject_code', 'like', "%{$this->search}%")->orWhere('edp_code', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%"); }); }

        $subjects = $query->orderBy('edp_code', 'asc')->paginate(10);

        $sectionsQuery = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') { $sectionsQuery->whereRaw('1 = 0'); }
        if (! $isPowerUser) { $sectionsQuery->where('department', $user->department); } elseif ($isPowerUser && ! empty($this->selectedDept)) { $sectionsQuery->where('department', $this->selectedDept); }

        $sections = $sectionsQuery->distinct()->pluck('section')->filter()->sort()->values();

        return view('livewire.manage-subjects', [
            'subjects'        => $subjects,
            'availableMajors' => $this->getAvailableMajorsProperty(),
            'sections'        => $sections,
            'activities'      => \App\Models\Activity::with('user')->latest()->take(10)->get(),
            'isPowerUser'     => $isPowerUser,
            'activePeriod'    => $period,
            'catalogMode'     => $this->catalogMode,
            'archiveOptions'  => $archiveOptions,
            'isArchiveMode'   => $isArchiveMode,
        ]);
    }
}