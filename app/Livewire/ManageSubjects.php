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
    public $duration_hours = 3;
    public $meetings_per_week = 1;

    // CSV Import Logic
    public $importFile;
    public $previewData = [];
    public $selectedSubjects = [];
    public $selectAll = false;
    public $showDuplicateConfirmModal = false;
    public $duplicateCandidateId = null;

    protected $listeners = ['refreshComponent' => '$refresh'];

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

    /**
     * Get available majors based on the selected department.
     */
    public function getAvailableMajorsProperty()
    {
        if (empty($this->department)) {
            return [];
        }

        return $this->majorsByDept[strtoupper($this->department)] ?? [];
    }

    /**
     * Auto-generate EDP code when major and year_level are both set.
     */
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

    /**
     * Generate an EDP code from the active academic workspace.
     *
     * New format: [MAJOR]-[YY][SEM][LEVEL][SEQ]  e.g. IT-2611001
     *   YY    = last 2 digits of start year (2026-2027 → "26")
     *   SEM   = semester digit (1=1st, 2=2nd, 3=Summer)
     *   LEVEL = year level
     *   SEQ   = 3-digit zero-padded sequence, auto-incremented per workspace
     */
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

    /**
     * Check if subject code is duplicate within same major / year / section.
     * Allows the same subject code in DIFFERENT sections.
     */
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

    /**
     * Normalise a year-level value that may arrive as a plain integer string
     * ("1"), an ordinal ("1st"), or a full phrase ("1st Year" / "Year 1").
     *
     * Delegates to EdpCodeService::yearLevelDigit() so the mapping stays in
     * one place. Returns an integer 1–4.
     */
    private function normalizeYearLevel(mixed $raw): int
    {
        $edpService = app(\App\Services\EdpCodeService::class);
        return $edpService->yearLevelDigit($raw ?? 1);
    }

    /**
     * Validate department access based on user role.
     */
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

    // ============================================================
    // CSV IMPORT
    // ============================================================

    /**
     * Preview CSV on upload — shows the first 10 rows with:
     *   • duplicate flag (already exists in workspace)
     *   • format flag (new vs legacy vs invalid)
     */
    public function updatedImportFile()
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);

        usleep(500000);

        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headerRow = array_shift($data);

        $normalizedHeader = array_map(fn ($h) => strtolower(trim($h)), $headerRow);

        if (in_array('room_name', $normalizedHeader) || in_array('building', $normalizedHeader)) {
            $this->abortImport('Room Registry');
            return;
        }

        if (in_array('faculty_name', $normalizedHeader) || in_array('employee_id', $normalizedHeader)) {
            $this->abortImport('Faculty Directory');
            return;
        }

        $required    = ['edp_code', 'subject_code', 'section', 'description', 'major', 'year_level', 'units', 'department', 'duration_hours', 'meetings_per_week'];
        $missing     = array_diff($required, $normalizedHeader);
        $hasSubjectType = in_array('type', $normalizedHeader, true) || in_array('subject_type', $normalizedHeader, true);

        if ($missing || ! $hasSubjectType) {
            $this->abortImport('Invalid Subject Template');
            return;
        }

        $indexes    = array_flip($normalizedHeader);
        $period     = $this->activePeriod();
        $edpService = app(EdpCodeService::class);

        $this->previewData = collect(array_slice($data, 0, 10))
            ->map(function ($row, $index) use ($indexes, $period, $edpService) {
                $edp    = strtoupper(trim($row[$indexes['edp_code']] ?? ''));
                $exists = $edp !== '' && Subject::edpExistsInWorkspace($edp, $period['school_year'], $period['semester']);

                // Format badge: 'new' | 'legacy' | 'invalid' | 'empty'
                $formatLabel = $edp !== '' ? $edpService->formatLabel($edp) : 'empty';

                // Semester-mismatch check: only meaningful for codes that pass format validation
                $semesterMismatch = $formatLabel === 'new'
                    && ! $edpService->validateSemesterMatch($edp, $period['semester']);

                return [
                    'row'               => $index + 2, // +2 = 1-based + skipped header
                    'edp_code'          => $edp,
                    'subject'           => $row[$indexes['subject_code']] ?? '',
                    'exists'            => $exists,
                    'format_label'      => $formatLabel,
                    'semester_mismatch' => $semesterMismatch, // true = wrong semester digit for active workspace
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

    /**
     * Import subjects from CSV.
     *
     * Validation enforced per row:
     *   1. EDP code must NOT be empty.
     *   2. EDP code must match the NEW 7-digit format: [MAJOR]-[YY][SEM][LEVEL][SEQ]
     *      e.g. IT-2611001  — old format (IT-261001) is REJECTED.
     *   3. EDP code must not already exist in the active workspace.
     *   4. User must have department access for the row's department.
     */
    public function importSubjects()
    {
        if (! $this->importFile) {
            return;
        }

        $path   = $this->importFile->getRealPath();
        $file   = fopen($path, 'r');
        $header = true;
        $indexes = [];

        $count        = 0;
        $skipped      = 0;
        $formatErrors   = []; // format / legacy EDP code errors
        $semesterErrors = []; // correct format but wrong semester digit
        $detectedDept = null;
        $actor        = auth()->user();

        $userRole    = strtolower($actor->role);
        $powerRoles  = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        $edpService = app(EdpCodeService::class);
        $period     = $this->activePeriod();
        $rowNumber  = 1; // header row

        if (empty($period['semester']) || empty($period['school_year'])) {
            fclose($file);
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'No Active Workspace',
                'detail'  => 'No active semester workspace found. Please configure the academic period in Settings before importing.',
            ]);
            return;
        }

        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            // Skip header row
            if ($header) {
                $indexes = array_flip(array_map(fn ($h) => strtolower(trim($h)), $row));
                $header  = false;
                continue;
            }

            $rowNumber++;
            $value = fn (string $key, $default = '') => trim((string) ($row[$indexes[$key] ?? -1] ?? $default));
            $typeColumn = array_key_exists('subject_type', $indexes) ? 'subject_type' : 'type';

            // Skip rows with empty EDP code
            if ($value('edp_code') === '') {
                $skipped++;
                continue;
            }

            $edpCode  = strtoupper($value('edp_code'));
            $rowDept  = strtoupper($value('department'));

            // -------------------------------------------------------
            // VALIDATION 1a: New EDP format enforcement
            // -------------------------------------------------------
            // Only the 7-digit format is accepted:  [MAJOR]-[YY][SEM][LEVEL][SEQ]
            // Example valid:   IT-2621001  (2nd semester)
            // Example invalid: IT-261001   (old 6-digit — REJECTED)
            // -------------------------------------------------------
            if (! $edpService->isValidForCreation($edpCode)) {
                $formatErrors[] = $edpService->validationMessage($edpCode, $rowNumber);
                $skipped++;
                continue;
            }

            // -------------------------------------------------------
            // VALIDATION 1b: Semester digit must match active workspace
            // -------------------------------------------------------
            // e.g. workspace = 2nd Semester → digit must be "2"
            //      IT-2611001 has digit "1" → REJECTED
            //      IT-2621001 has digit "2" → ACCEPTED
            // -------------------------------------------------------
            if (! $edpService->validateSemesterMatch($edpCode, $period['semester'])) {
                $semesterErrors[] = $edpService->semesterMismatchMessage($edpCode, $period['semester'], $rowNumber);
                $skipped++;
                continue;
            }

            // -------------------------------------------------------
            // VALIDATION 2: Department access
            // -------------------------------------------------------
            if (! $this->validateDepartmentAccess($rowDept)) {
                $skipped++;
                continue;
            }

            if (! $detectedDept && ! empty($rowDept)) {
                $detectedDept = $rowDept;
            }

            // -------------------------------------------------------
            // VALIDATION 3: Workspace duplicate check
            // -------------------------------------------------------
            $exists  = Subject::edpExistsInWorkspace($edpCode, $period['school_year'], $period['semester']);

            if ($exists) {
                \Log::warning("CSV import row {$rowNumber}: EDP code {$edpCode} already exists in {$period['semester']} {$period['school_year']}. Skipping.");
                $skipped++;
                continue;
            }

            // -------------------------------------------------------
            // All validations passed — create the subject
            // -------------------------------------------------------
            $rowMajor      = strtoupper($value('major'));
            // normalizeYearLevel() handles "1st Year", "Year 1", "1st", "1", etc.
            $rowYearLevel  = $this->normalizeYearLevel($value('year_level', 1));
            $rawDuration   = $value('duration_hours', '3');
            $rawType       = $value($typeColumn, 'Major');
            $rawMeetings   = (int) $value('meetings_per_week', 1);
            $rawUnits      = (int) $value('units', 3);
            $clampedUnits  = max(3, min(5, $rawUnits));
            $section       = strtoupper($value('section', 'A'));
            $normalizedType = str_contains(strtolower($rawType), 'minor') ? 'Minor' : 'Major';
            $specialization = strtoupper($value('specialization', $rowMajor));
            $requiresLab = filter_var($value('requires_lab', false), FILTER_VALIDATE_BOOLEAN)
                || str_contains(strtoupper($value('preferred_room_type', '')), 'LAB')
                || str_contains(strtoupper($rawType.' '.$value('description').' '.$specialization), 'LAB');
            $preferredRoomType = strtoupper($value('preferred_room_type', $requiresLab ? 'LAB' : 'LECTURE'));

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
                'preferred_room_type' => $preferredRoomType,
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

        // ── Show per-row semester-digit mismatch errors ──────────────
        if (! empty($semesterErrors)) {
            $semSample = array_slice($semesterErrors, 0, 3);
            $more = count($semesterErrors) > 3
                ? (' … and ' . (count($semesterErrors) - 3) . ' more.')
                : '';
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Wrong Semester in EDP Code',
                'detail'  => implode(' | ', $semSample) . $more,
            ]);
        }

        // ── Show per-row format errors ────────────────────────────────
        if (! empty($formatErrors)) {
            $errorSample = array_slice($formatErrors, 0, 5);
            $more = count($formatErrors) > 5 ? (' … and ' . (count($formatErrors) - 5) . ' more.') : '';
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Invalid EDP Code Format',
                'detail'  => implode(' | ', $errorSample) . $more,
            ]);
        }

        if ($count > 0) {
            $targetDept = ! empty($this->selectedDept)
                ? $this->selectedDept
                : ($detectedDept ?? $actor->department ?? 'General');

            Activity::create([
                'user_id'     => $actor->id,
                'action'      => 'Import',
                'module'      => 'Subjects',
                'description' => "Successfully batch-imported {$count} subjects into the {$targetDept} department."
                    . ($skipped > 0 ? " ({$skipped} rows skipped due to validation errors.)" : ''),
            ]);

            $recipients = User::where('id', '!=', $actor->id)
                ->where(function ($query) use ($targetDept) {
                    $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                          ->orWhere(function ($q) use ($targetDept) {
                              $q->whereIn('role', ['dean', 'oic'])
                                ->where('department', $targetDept);
                          });
                })->get();

            if ($recipients->count() > 0) {
                Notification::send($recipients, new SubjectUpdatedNotification(
                    (object) [
                        'subject_code'        => 'BATCH IMPORT',
                        'subject_description' => "{$count} Subjects synchronized for {$targetDept}",
                    ],
                    'subject_imported'
                ));
            }

            $this->dispatch('notify', [
                'type'        => 'success',
                'title'       => 'CATALOG SYNCED',
                'message'     => "Successfully batch-imported {$count} subjects."
                    . ($skipped > 0 ? " ({$skipped} skipped.)" : ''),
                'sender_name' => $actor->name,
            ]);

            $this->dispatch('subjectUpdated');

            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Import Complete',
                'detail'  => "{$count} subjects added for {$targetDept}."
                    . ($skipped > 0 ? " {$skipped} rows were skipped." : ''),
            ]);
        } else {
            $allRejected = ! empty($formatErrors) || ! empty($semesterErrors);
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Import Failed',
                'detail'  => ! $allRejected
                    ? 'No valid subjects found or unauthorized department access.'
                    : 'All rows were rejected. Check that EDP codes use the correct format '
                        . 'and the correct semester digit for the active workspace '
                        . '(e.g. IT-' . $period['edp_prefix'] . '1001 for '
                        . Setting::semesterLabel($period['semester']) . ').',
            ]);
        }

        $this->reset(['importFile', 'bulkOpen', 'previewData']);
    }

    // ============================================================
    // MODAL MANAGEMENT
    // ============================================================

    /**
     * Open modal to create a new subject.
     */
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
        $this->major            = '';
        $this->year_level       = '';
        $this->edp_code         = '';
        $this->subject_code     = '';
        $this->section          = '';

        $user     = auth()->user();
        $userRole = strtolower($user->role);

        $this->department = in_array($userRole, ['admin', 'registrar', 'associate_dean'])
            ? ''
            : $user->department;

        $this->showModal = true;
    }

    /**
     * Load an existing subject into the edit form.
     */
    public function editSubject($id)
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => 'Archive is read only',
                'detail'  => 'Switch back to the current semester before editing subjects.',
            ]);
            return;
        }

        $this->resetValidation();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);

        if (! $this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Access Denied',
                'detail'  => 'You do not have permission to edit subjects in this department.',
            ]);
            return;
        }

        $this->isEditMode        = true;
        $this->subjectId         = $subject->id;
        $this->edp_code          = $subject->edp_code;
        $this->subject_code      = $subject->subject_code;
        $this->section           = $subject->section;
        $this->description       = $subject->description;
        $this->units             = $subject->units;
        $this->type              = $subject->type ?? 'Major';
        $this->requires_lab      = (bool) ($subject->requires_lab ?? false);
        $this->preferred_room_type = $subject->preferred_room_type ?? '';
        $this->duration_hours    = $subject->duration_hours ?? 3;
        $this->meetings_per_week = $subject->meetings_per_week ?? 1;
        $this->major             = $subject->major ?? '';
        $this->year_level        = $subject->year_level ?? 1;
        $this->department        = $subject->department;

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

    /**
     * Validate and save a subject (create or update).
     *
     * EDP code validation rules enforced here:
     *   • Required.
     *   • Must match /^[A-Z]{2,4}-\d{7}$/ — the new 7-digit format only.
     *     Invalid: IT-261001 (old 6-digit), IT2611001 (no dash), IT-26A1001 (alpha in digits).
     *   • Must be unique within the active semester workspace.
     */
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

        $edpUpper         = strtoupper(trim($this->edp_code));
        $sectionUpper     = strtoupper($this->section);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper       = strtoupper($this->major);
        $period           = $this->activePeriod();

        // -------------------------------------------------------
        // VALIDATION — includes strict EDP format enforcement
        // -------------------------------------------------------
        $this->validate([
            'edp_code' => [
                'required',
                // Format: [MAJOR]-[YY][SEM][LEVEL][SEQ]
                //   MAJOR  = 2–4 uppercase letters  (e.g. IT)
                //   YY     = 2-digit school-year start (2026-2027 → 26)
                //   SEM    = semester digit: 1=1st · 2=2nd · 3=Summer
                //   LEVEL  = year level: 1=1st · 2=2nd · 3=3rd · 4=4th
                //   SEQ    = 3-digit zero-padded sequence (001, 002, …)
                //   Valid:   IT-2611001   IT-2621001   IT-2631001
                //   Invalid: IT-261001 (old 6-digit)  IT2611001 (no dash)
                'regex:/^[A-Z]{2,4}-\d{7}$/',
                Rule::unique('subjects', 'edp_code')
                    ->where(fn ($query) => $query
                        ->where('school_year', $period['school_year'])
                        ->where('semester',    $period['semester']))
                    ->ignore($this->subjectId),
            ],
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
            'edp_code.regex'    => 'Invalid EDP code format. '
                . 'Required: [MAJOR]-[YY][SEM][LEVEL][SEQ] — e.g. IT-2611001 '
                . '(26=year · 1=1st sem · 1=1st year · 001=sequence). '
                . 'The old 6-digit format (e.g. IT-261001) is no longer accepted.',
            'edp_code.unique'   => "EDP code \"{$edpUpper}\" already exists in {$period['semester']} {$period['school_year']}.",
        ]);

        // ── VALIDATION B: Legacy-code specific message ────────────────
        $edpService = app(EdpCodeService::class);
        if (! $edpService->isNew($edpUpper)) {
            $this->addError('edp_code', $edpService->validationMessage($edpUpper));
            return;
        }

        // ── VALIDATION C: Semester digit must match active workspace ──
        // The 3rd numeric digit after the dash encodes the semester.
        // e.g. active workspace = 2nd Semester → digit must be "2"
        //      IT-2611001 digit "1" → REJECTED  (1st Semester code in 2nd Semester workspace)
        //      IT-2621001 digit "2" → ACCEPTED
        if (! $edpService->validateSemesterMatch($edpUpper, $period['semester'])) {
            $this->addError('edp_code', $edpService->semesterMismatchMessage($edpUpper, $period['semester']));
            return;
        }

        // Duplicate subject-code check (only on create)
        if (! $this->isEditMode && $this->getSubjectCodeDuplicateProperty()) {
            $this->addError('subject_code', "Subject code '{$subjectCodeUpper}' already exists in Section {$sectionUpper} for {$majorUpper} - Year {$this->year_level}.");
            return;
        }

        // Extra workspace duplicate guard on create
        if (! $this->isEditMode && Subject::edpExistsInWorkspace($edpUpper, $period['school_year'], $period['semester'])) {
            $this->addError('edp_code', "EDP code '{$edpUpper}' already exists in {$period['semester']} {$period['school_year']}.");
            return;
        }

        $this->executeSave();
    }

    /**
     * Execute the actual database write after all validation passes.
     */
    public function executeSave()
    {
        $user             = auth()->user();
        $deptUpper        = strtoupper($this->department);
        $edpUpper         = strtoupper($this->edp_code);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper       = strtoupper($this->major);
        $period           = $this->activePeriod();

        $normalizedType = in_array($this->type, ['Major', 'Minor']) ? $this->type : 'Major';

        $subject = Subject::updateOrCreate(
            ['id' => $this->subjectId],
            [
                'edp_code'          => $edpUpper,
                'subject_code'      => $subjectCodeUpper,
                'section'           => strtoupper($this->section),
                'description'       => $this->description,
                'major'             => $majorUpper,
                'year_level'        => (int) $this->year_level,
                'units'             => (int) $this->units,
                'type'              => $normalizedType,
                'subject_type'      => $normalizedType,
                'requires_lab'      => (bool) $this->requires_lab,
                'preferred_room_type' => $this->preferred_room_type ?: ((bool) $this->requires_lab ? 'LAB' : 'LECTURE'),
                'specialization'    => $majorUpper,
                'duration_hours'    => (float) $this->duration_hours,
                'meetings_per_week' => (int) $this->meetings_per_week,
                'department'        => $deptUpper,
                'semester'          => $period['semester'],
                'school_year'       => $period['school_year'],
                'academic_year'     => $period['school_year'],
                'workspace_key'     => $period['workspace_key'],
                'is_archived'       => false,
                'archived_at'       => null,
                'archive_batch'     => null,
            ]
        );

        $this->logActivityAndNotify($subject, $user, $deptUpper);

        $this->showDuplicateConfirmModal = false;
        $this->showModal = false;

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $this->isEditMode ? 'Subject Updated' : 'Subject Created',
            'detail'  => "{$subject->subject_code} is now synchronized.",
        ]);

        $this->dispatch('subjectUpdated');
        $this->completeFormReset();
    }

    private function completeFormReset(): void
    {
        $this->reset([
            'edp_code', 'subject_code', 'section', 'description',
            'units', 'type', 'duration_hours', 'major', 'year_level',
            'department', 'subjectId', 'isEditMode', 'meetings_per_week',
            'requires_lab', 'preferred_room_type',
        ]);
    }

    private function logActivityAndNotify($subject, $user, $deptUpper): void
    {
        Activity::create([
            'user_id'     => $user->id,
            'action'      => $this->isEditMode ? 'Update' : 'Add',
            'module'      => 'Subjects',
            'description' => $this->isEditMode
                ? "Updated {$subject->subject_code} in {$deptUpper}."
                : "Manually added {$subject->subject_code} to {$deptUpper}.",
        ]);

        $recipients = User::where('id', '!=', $user->id)
            ->where(function ($query) use ($deptUpper) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                      ->orWhere(function ($q) use ($deptUpper) {
                          $q->whereIn('role', ['dean', 'oic'])->where('department', $deptUpper);
                      });
            })->get();

        if ($recipients->count() > 0) {
            Notification::send($recipients, new SubjectUpdatedNotification(
                $subject,
                $this->isEditMode ? 'updated' : 'created'
            ));
        }
    }

    // ============================================================
    // BULK OPERATIONS
    // ============================================================

    /**
     * Duplicate selected subjects into the next section.
     * New EDP codes are auto-generated using the current workspace.
     */
    public function bulkDuplicate()
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => 'Archive is read only',
                'detail'  => 'Switch back to the current semester before duplicating subjects.',
            ]);
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

            if (! $this->validateDepartmentAccess($original->department)) {
                $skippedCount++;
                continue;
            }

            $currentSection = strtoupper($original->section ?: 'A');
            $nextSection    = $currentSection === 'Z'  ? 'AA'
                : ($currentSection === 'AA' ? 'AB'
                : chr(ord($currentSection) + 1));

            // Auto-generate a new EDP code in the new 7-digit format
            $newEdp = Subject::generateEdpCode(
                $original->major ?: strtok((string) $original->edp_code, '-'),
                (int) $original->year_level,
                $period['school_year'],
                $period['semester']
            );

            $subjectExistsInNextSection = $this->activeSubjectsQuery()
                ->where('subject_code', strtoupper($original->subject_code))
                ->where('section',      $nextSection)
                ->where('major',        $original->major)
                ->where('year_level',   $original->year_level)
                ->where('department',   $original->department)
                ->exists();

            if ($subjectExistsInNextSection) {
                $skippedCount++;
                $skippedReasons[] = "{$original->subject_code} in Section {$nextSection} already exists";
                continue;
            }

            if (Subject::edpExistsInWorkspace($newEdp, $period['school_year'], $period['semester'])) {
                $skippedCount++;
                continue;
            }

            try {
                Subject::create([
                    'edp_code'          => $newEdp,
                    'subject_code'      => $original->subject_code,
                    'section'           => $nextSection,
                    'description'       => $original->description,
                    'major'             => $original->major,
                    'year_level'        => $original->year_level,
                    'units'             => $original->units,
                    'department'        => $original->department,
                    'type'              => $original->type ?? 'Major',
                    'subject_type'      => $original->subject_type,
                    'requires_lab'      => (bool) ($original->requires_lab ?? false),
                    'preferred_room_type' => $original->preferred_room_type,
                    'specialization'    => $original->specialization,
                    'duration_hours'    => $original->duration_hours,
                    'meetings_per_week' => $original->meetings_per_week ?? 1,
                    'semester'          => $period['semester'],
                    'school_year'       => $period['school_year'],
                    'academic_year'     => $period['school_year'],
                    'workspace_key'     => $period['workspace_key'],
                    'is_archived'       => false,
                ]);

                $duplicatedCount++;
            } catch (\Exception $e) {
                \Log::error("Error duplicating subject {$original->id}: " . $e->getMessage());
                $skippedCount++;
            }
        }

        Activity::create([
            'user_id'     => $actor->id,
            'action'      => 'Bulk Duplicate',
            'module'      => 'Subjects',
            'description' => "Created {$duplicatedCount} new subject sections via bulk duplication."
                . ($skippedCount > 0 ? " ({$skippedCount} skipped — already exist)" : ''),
        ]);

        $this->reset(['selectedSubjects', 'selectAll']);
        $this->dispatch('subjectUpdated');

        if ($skippedCount > 0) {
            $detail = "Duplicated {$duplicatedCount} subjects, {$skippedCount} skipped.";
            if (! empty($skippedReasons)) {
                $detail .= ' Reasons: ' . implode(', ', array_slice($skippedReasons, 0, 3));
                if (count($skippedReasons) > 3) {
                    $detail .= ', and more…';
                }
            }

            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Bulk Duplicate Partial', 'detail' => $detail]);
        } else {
            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Bulk Duplicate Complete',
                'detail'  => "{$duplicatedCount} subjects have been copied to the next section.",
            ]);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($this->catalogMode !== 'active') {
            $this->selectAll       = false;
            $this->selectedSubjects = [];
            return;
        }

        if ($value) {
            $user        = auth()->user();
            $userRole    = strtolower($user->role ?? '');
            $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);

            $query = $this->activeSubjectsQuery();

            if (! $isPowerUser) {
                $query->where('department', $user->department);
            } elseif (! empty($this->selectedDept)) {
                $query->where('department', $this->selectedDept);
            }

            if (! empty($this->selectedSection)) {
                $query->where('section', $this->selectedSection);
            }

            if (! empty($this->search)) {
                $query->where(function ($q) {
                    $q->where('subject_code', 'like', "%{$this->search}%")
                      ->orWhere('edp_code',   'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }

            if (! empty($this->selectedYear)) {
                $query->where('year_level', $this->selectedYear);
            }

            if (! empty($this->selectedMajor)) {
                $query->where('major', strtoupper($this->selectedMajor));
            }

            $this->selectedSubjects = $query
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedSubjects = [];
        }
    }

    public function deleteSelected()
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => 'Archive is read only',
                'detail'  => 'Switch back to the current semester before editing subjects.',
            ]);
            return;
        }

        $count = count($this->selectedSubjects);
        $user  = auth()->user();

        if ($count > 0) {
            $sampleSubject = $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->first();
            $targetDept    = $sampleSubject ? $sampleSubject->department : ($user->department ?? 'General');

            if (! $this->validateDepartmentAccess($targetDept)) {
                $this->dispatch('toast', [
                    'type'    => 'error',
                    'message' => 'Access Denied',
                    'detail'  => 'You do not have permission to delete subjects in this department.',
                ]);
                return;
            }

            $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->delete();

            Activity::create([
                'user_id'     => $user->id,
                'action'      => 'Delete',
                'module'      => 'Subjects',
                'description' => "Bulk removed {$count} subjects from the {$targetDept} catalog.",
            ]);

            $recipients = User::where('id', '!=', $user->id)
                ->where(function ($query) use ($targetDept) {
                    $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                          ->orWhere(function ($q) use ($targetDept) {
                              $q->whereIn('role', ['dean', 'oic'])
                                ->where('department', $targetDept);
                          });
                })->get();

            if ($recipients->count() > 0) {
                Notification::send($recipients, new SubjectUpdatedNotification(
                    (object) [
                        'subject_code'        => 'BATCH PURGE',
                        'subject_description' => "{$count} Records removed from {$targetDept}",
                    ],
                    'deleted'
                ));
            }

            $this->dispatch('notify', [
                'type'        => 'error',
                'title'       => 'REGISTRY PURGED',
                'message'     => "Successfully removed {$count} subjects from the database.",
                'sender_name' => $user->name,
            ]);

            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Batch Deleted',
                'detail'  => "{$count} subjects successfully removed.",
            ]);

            $this->dispatch('subjectUpdated');
            $this->reset(['selectedSubjects', 'selectAll']);
        }
    }

    public function deleteSubject($id)
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => 'Archive is read only',
                'detail'  => 'Archived subjects cannot be edited or deleted from this view.',
            ]);
            return;
        }

        $user    = auth()->user();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);

        if (! $this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Access Denied',
                'detail'  => 'You do not have permission to delete subjects in this department.',
            ]);
            return;
        }

        $subjectCode = $subject->subject_code;
        $subjectDesc = $subject->description;
        $targetDept  = $subject->department;

        $recipients = User::where('id', '!=', $user->id)
            ->where(function ($query) use ($targetDept) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                      ->orWhere(function ($q) use ($targetDept) {
                          $q->whereIn('role', ['dean', 'oic'])
                            ->where('department', $targetDept);
                      });
            })->get();

        if ($recipients->count() > 0) {
            Notification::send($recipients, new SubjectUpdatedNotification($subject, 'deleted'));
        }

        $subject->delete();

        Activity::create([
            'user_id'     => $user->id,
            'action'      => 'Delete',
            'module'      => 'Subjects',
            'description' => "Manually removed subject {$subjectCode} from the {$targetDept} catalog.",
        ]);

        $this->dispatch('notify', [
            'type'        => 'error',
            'title'       => 'SUBJECT REMOVED',
            'message'     => "{$subjectCode} has been deleted from the registry.",
            'sender_name' => $user->name,
        ]);

        $this->dispatch('toast', [
            'type'    => 'warning',
            'message' => 'Subject Deleted',
            'detail'  => "{$subjectCode} - {$subjectDesc} removed.",
        ]);

        $this->dispatch('subjectUpdated');
    }

    // ============================================================
    // LIFECYCLE
    // ============================================================

    public function mount()
    {
        $user     = auth()->user();
        $userRole = strtolower($user->role);

        $this->selectedDept = in_array($userRole, ['admin', 'registrar', 'associate_dean'])
            ? ''
            : $user->department;
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

        if ($isArchiveMode && $this->selectedArchiveBatch === '') {
            $query->whereRaw('1 = 0');
        }

        if (! $isPowerUser) {
            $query->where('department', $user->department);
        } elseif (! empty($this->selectedDept)) {
            $query->where('department', $this->selectedDept);
        }

        if (! empty($this->selectedSection)) {
            $query->where('section', $this->selectedSection);
        }

        if (! empty($this->selectedYear)) {
            $query->where('year_level', (int) $this->selectedYear);
        }

        if (! empty($this->selectedMajor)) {
            $query->where('major', strtoupper($this->selectedMajor));
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('subject_code', 'like', "%{$this->search}%")
                  ->orWhere('edp_code',   'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $subjects = $query->orderBy('edp_code', 'asc')->paginate(10);

        $sectionsQuery = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') {
            $sectionsQuery->whereRaw('1 = 0');
        }

        if (! $isPowerUser) {
            $sectionsQuery->where('department', $user->department);
        } elseif ($isPowerUser && ! empty($this->selectedDept)) {
            $sectionsQuery->where('department', $this->selectedDept);
        }

        $sections = $sectionsQuery
            ->distinct()
            ->pluck('section')
            ->filter()
            ->sort()
            ->values();

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
