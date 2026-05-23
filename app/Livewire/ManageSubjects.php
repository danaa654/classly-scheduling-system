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
        'CCS' => ['IT' => 'Information Technology', 'ACT' => 'Assistive Computer Technology'],
        'SHTM' => ['HM' => 'Hospitality Management', 'TM' => 'Tourism Management'],
        'COC' => ['FB' => 'Forensic Biology', 'LD' => 'Lie Detection', 'QD' => 'Questioned Documents'],
        'CTE' => ['ED' => 'Education'],
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
                'semester' => $archive->semester,
                'semester_name' => Setting::semesterDisplayName($archive->semester, $archive->academic_year),
                'school_year' => $archive->academic_year,
                'total_subjects' => null,
                'archived_at' => null,
            ]);
    }

    /**
     * Get available majors based on selected department
     */
    public function getAvailableMajorsProperty()
    {
        if (empty($this->department)) {
            return [];
        }

        $dept = strtoupper($this->department);
        return $this->majorsByDept[$dept] ?? [];
    }

    /**
     * Auto-generate EDP code when major and year_level are selected
     */
    public function updatedMajor($value)
    {
        if (!empty($value) && !empty($this->year_level)) {
            $this->generateEdpCode();
        }
    }

    public function updatedYearLevel($value)
    {
        if (!empty($value) && !empty($this->major)) {
            $this->generateEdpCode();
        }
    }

    /**
     * Generate EDP codes from the active academic workspace.
     *
     * Format: MAJOR-[YY][SEM][LEVEL][###], e.g. IT-2621001.
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
     * ✅ FIXED: Check if subject code is duplicate within same major/year/SECTION
     * Now it correctly allows the same subject code in DIFFERENT sections
     */
    public function getSubjectCodeDuplicateProperty()
    {
        if (empty($this->subject_code) || empty($this->major) || empty($this->year_level) || empty($this->section)) {
            return false;
        }

        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper = strtoupper($this->major);
        $yearLevel = (int)$this->year_level;
        $sectionUpper = strtoupper($this->section);

        $query = $this->activeSubjectsQuery()->where('subject_code', $subjectCodeUpper)
            ->where('major', $majorUpper)
            ->where('year_level', $yearLevel)
            ->where('section', $sectionUpper); // ✅ CRITICAL: Check SECTION too!

        // Exclude current subject when editing
        if ($this->isEditMode && $this->subjectId) {
            $query->where('id', '!=', $this->subjectId);
        }

        return $query->exists();
    }

    /**
     * Validate Department Access based on user role
     */
    private function validateDepartmentAccess($dept)
    {
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];

        // Power users can access all departments
        if (in_array($userRole, $powerRoles)) {
            return true;
        }

        // Dean and OIC can only access their own department
        if (in_array($userRole, ['dean', 'oic'])) {
            return strtoupper($dept) === strtoupper($user->department);
        }

        return false;
    }

    /**
     * Handle CSV Import
     */
    public function updatedImportFile()
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);

        usleep(500000); 

        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headerRow = array_shift($data);
        
        $normalizedHeader = array_map(fn($h) => strtolower(trim($h)), $headerRow);

        // Security checks
        if (in_array('room_name', $normalizedHeader) || in_array('building', $normalizedHeader)) {
            $this->abortImport('Room Registry');
            return;
        }

        if (in_array('faculty_name', $normalizedHeader) || in_array('employee_id', $normalizedHeader)) {
            $this->abortImport('Faculty Directory');
            return;
        }

        $required = ['edp_code', 'subject_code', 'section', 'description', 'major', 'year_level', 'units', 'department', 'duration_hours', 'meetings_per_week'];
        $missing = array_diff($required, $normalizedHeader);
        $hasSubjectType = in_array('type', $normalizedHeader, true) || in_array('subject_type', $normalizedHeader, true);

        if ($missing || !$hasSubjectType) {
            $this->abortImport('Invalid Subject Template');
            return;
        }

        // Preview data
        $indexes = array_flip($normalizedHeader);
        $period = $this->activePeriod();
        $this->previewData = collect(array_slice($data, 0, 10))->map(function ($row) use ($indexes, $period) {
            $edp = strtoupper(trim($row[$indexes['edp_code']] ?? ''));
            $exists = $edp !== '' && Subject::edpExistsInWorkspace($edp, $period['school_year'], $period['semester']);
            return [
                'edp_code' => $edp,
                'subject' => $row[$indexes['subject_code']] ?? '',
                'exists' => $exists
            ];
        })->toArray();
    }

    private function abortImport($detectedType)
    {
        $this->reset(['importFile', 'previewData']);
        $this->dispatch('toast', [
            'type' => 'warning',
            'message' => 'Incorrect CSV Detected',
            'detail' => "This file appears to be for the {$detectedType}. Please upload the Subject CSV template instead."
        ]);
    }

    /**
     * Import Subjects from CSV
     */
    public function importSubjects()
    {
        if (!$this->importFile) return;

        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        $header = true;
        $indexes = [];
        $count = 0;
        $detectedDept = null; 
        $actor = auth()->user();

        $userRole = strtolower($actor->role);
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            if ($header) {
                $indexes = array_flip(array_map(fn ($h) => strtolower(trim($h)), $row));
                $header = false;
                continue;
            }

            $value = fn (string $key, $default = '') => trim((string) ($row[$indexes[$key] ?? -1] ?? $default));
            $typeColumn = array_key_exists('subject_type', $indexes) ? 'subject_type' : 'type';

            if ($value('edp_code') === '') continue;

            $rowDept       = strtoupper($value('department'));
            $rowSection    = strtoupper($value('section', 'A'));
            $rowMajor      = strtoupper($value('major'));
            $rowYearLevel  = (int) $value('year_level', 1);
            
            // Authorization check
            if (!$this->validateDepartmentAccess($rowDept)) {
                continue; 
            }

            if (!$detectedDept && !empty($rowDept)) {
                $detectedDept = $rowDept;
            }

            $rawDuration = $value('duration_hours', '3');
            $rawType     = $value($typeColumn, 'Major');
            $rawMeetings = (int) $value('meetings_per_week', 1);
            $rawUnits    = (int) $value('units', 3);
            $clampedUnits = max(3, min(5, $rawUnits));

            $period = $this->activePeriod();
            $edpCode = strtoupper($value('edp_code'));
            $section = strtoupper($value('section', 'A'));

            // EDP codes are unique only inside the active semester workspace.
            $exists = Subject::edpExistsInWorkspace($edpCode, $period['school_year'], $period['semester']);

            if ($exists) {
                \Log::warning("Subject {$edpCode} already exists in {$period['semester']} {$period['school_year']}. Skipping import.");
                continue;
            }

            // Normalize type
            $normalizedType = str_contains(strtolower($rawType), 'minor') ? 'Minor' : 'Major';
            $specialization = strtoupper($value('specialization', $rowMajor));

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

        if ($count > 0) {
            $targetDept = !empty($this->selectedDept) ? $this->selectedDept : ($detectedDept ?? $actor->department ?? 'General');

            Activity::create([
                'user_id'     => $actor->id,
                'action'      => 'Import',
                'module'      => 'Subjects',
                'description' => "Successfully batch-imported {$count} subjects into the {$targetDept} department.",
            ]);

            $recipients = User::where('id', '!=', $actor->id)
                ->where(function($query) use ($targetDept) {
                    $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                          ->orWhere(function($q) use ($targetDept) {
                              $q->whereIn('role', ['dean', 'oic'])
                                ->where('department', $targetDept);
                          });
                })->get();

            if ($recipients->count() > 0) {
                Notification::send($recipients, new SubjectUpdatedNotification(
                    (object)[
                        'subject_code' => 'BATCH IMPORT', 
                        'subject_description' => "{$count} Subjects synchronized for {$targetDept}"
                    ], 
                    'subject_imported' 
                ));
            }

            $this->dispatch('notify', [
                'type'        => 'success', 
                'title'       => 'CATALOG SYNCED',
                'message'     => "Successfully batch-imported {$count} subjects.",
                'sender_name' => $actor->name
            ]);

            $this->dispatch('subjectUpdated');

            $this->dispatch('toast', [
                'type'    => 'success', 
                'message' => 'Import Complete', 
                'detail'  => "{$count} subjects added/updated for {$targetDept}."
            ]);

        } else {
            $this->dispatch('toast', [
                'type'    => 'error', 
                'message' => 'Import Failed', 
                'detail'  => "No valid subjects found or unauthorized department access."
            ]);
        }

        $this->reset(['importFile', 'bulkOpen', 'previewData']);
    }

    /**
     * Open modal for new subject
     */
    public function openModal()
    {
        if ($this->catalogMode !== 'active') {
            $this->catalogMode = 'active';
            $this->selectedArchiveBatch = '';
        }

        $this->resetValidation();
        $this->resetExcept(['selectedDept', 'search', 'selectedYear', 'selectedMajor', 'selectedSection']);
        $this->isEditMode = false;
        $this->units = 3;
        $this->type = 'Major';
        $this->duration_hours = 3;
        $this->meetings_per_week = 1;
        $this->major = '';
        $this->year_level = '';
        $this->edp_code = '';
        $this->subject_code = '';
        $this->section = '';

        $user = auth()->user();
        $userRole = strtolower($user->role);
        $powerRoles = ['admin', 'registrar', 'associate_dean'];

        // Restrict access based on role
        if (!in_array($userRole, $powerRoles)) {
            // Dean and OIC can only manage their own department
            $this->department = $user->department;
        } else {
            $this->department = '';
        }

        $this->showModal = true;
    }

    /**
     * Edit existing subject
     */
    public function editSubject($id)
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Archive is read only',
                'detail' => 'Switch back to the current semester before editing subjects.'
            ]);
            return;
        }

        $this->resetValidation();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);

        // Validate access
        if (!$this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Access Denied',
                'detail' => 'You do not have permission to edit subjects in this department.'
            ]);
            return;
        }

        $this->isEditMode = true;
        $this->subjectId = $subject->id;
        $this->edp_code = $subject->edp_code;
        $this->subject_code = $subject->subject_code;
        $this->section = $subject->section;
        $this->description = $subject->description;
        $this->units = $subject->units;
        $this->type = $subject->type ?? 'Major';
        $this->duration_hours = $subject->duration_hours ?? 3;
        $this->meetings_per_week = $subject->meetings_per_week ?? 1;
        $this->major = $subject->major ?? '';
        $this->year_level = $subject->year_level ?? 1;
        $this->department = $subject->department;
        
        $this->showModal = true;
    }

    public function updatedUnits($value)
    {
        if (!is_numeric($value) || $value < 3) {
            $this->units = 3;
        } elseif ($value > 5) {
            $this->units = 5;
        }
    }

    /**
     * ✅ FIXED: Save Subject (Create or Update) - Only validate uniqueness when section changes
     */
    public function saveSubject()
    {
        if ($this->catalogMode !== 'active') {
            $this->addError('edp_code', 'Archived subjects are read only.');
            return;
        }

        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        // Validate department access
        if (!$isPowerUser && !$this->validateDepartmentAccess($this->department)) {
            $this->addError('department', 'You do not have permission to manage subjects in this department.');
            return;
        }

        $edpUpper = strtoupper($this->edp_code);
        $sectionUpper = strtoupper($this->section);
        $deptUpper = strtoupper($this->department);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper = strtoupper($this->major);
        $period = $this->activePeriod();

        // Validation
        $this->validate([
            'edp_code'       => [
                'required',
                Rule::unique('subjects', 'edp_code')
                    ->where(fn ($query) => $query
                        ->where('school_year', $period['school_year'])
                        ->where('semester', $period['semester']))
                    ->ignore($this->subjectId),
            ],
            'subject_code'   => 'required',
            'section'        => 'required|max:10',
            'department'     => 'required',
            'major'          => 'required',
            'year_level'     => 'required|integer|min:1|max:4',
            'description'    => 'required',
            'units'          => 'required|integer|min:3|max:5',
            'type'           => 'required|in:Major,Minor',
            'duration_hours' => 'required|numeric|min:1|max:10',
            'meetings_per_week' => 'required|integer|min:1|max:5',
        ]);

        // ✅ FIXED: Only check for duplicate subject code if NOT in edit mode
        // When editing, we allow the same subject code to remain in the same section
        // When creating new, we check if this code already exists in this SECTION
        if (!$this->isEditMode && $this->getSubjectCodeDuplicateProperty()) {
            $this->addError('subject_code', "Subject code '{$subjectCodeUpper}' already exists in Section {$sectionUpper} for {$majorUpper} - Year {$this->year_level}.");
            return;
        }

        // Check for duplicate EDP inside this semester workspace.
        if (!$this->isEditMode) {
            $duplicate = Subject::edpExistsInWorkspace($edpUpper, $period['school_year'], $period['semester']);

            if ($duplicate) {
                $this->addError('edp_code', "EDP code '{$edpUpper}' already exists in {$period['semester']} {$period['school_year']}.");
                return;
            }
        }

        $this->executeSave();
    }

    /**
     * Execute save operation
     */
    public function executeSave()
    {
        $user = auth()->user();
        $deptUpper = strtoupper($this->department);
        $edpUpper = strtoupper($this->edp_code);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper = strtoupper($this->major);
        $period = $this->activePeriod();

        // Normalize type
        $normalizedType = in_array($this->type, ['Major', 'Minor']) 
            ? $this->type 
            : 'Major';

        $subject = Subject::updateOrCreate(
            ['id' => $this->subjectId],
            [
                'edp_code'          => $edpUpper,
                'subject_code'      => $subjectCodeUpper,
                'section'           => strtoupper($this->section),
                'description'       => $this->description,
                'major'             => $majorUpper,
                'year_level'        => (int)$this->year_level,
                'units'             => (int)$this->units,
                'type'              => $normalizedType,
                'subject_type'      => $normalizedType,
                'specialization'    => $majorUpper,
                'duration_hours'    => (float)$this->duration_hours,
                'meetings_per_week' => (int)$this->meetings_per_week,
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
            'detail'  => "{$subject->subject_code} is now synchronized."
        ]);

        $this->dispatch('subjectUpdated');
        $this->completeFormReset();
    }

    /**
     * ✅ Complete form reset to prevent stale data
     */
    private function completeFormReset()
    {
        $this->reset([
            'edp_code', 
            'subject_code', 
            'section', 
            'description', 
            'units', 
            'type', 
            'duration_hours', 
            'major', 
            'year_level', 
            'department', 
            'subjectId', 
            'isEditMode',
            'meetings_per_week'
        ]);
    }

    private function logActivityAndNotify($subject, $user, $deptUpper)
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
            ->where(function($query) use ($deptUpper) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                      ->orWhere(function($q) use ($deptUpper) {
                          $q->whereIn('role', ['dean', 'oic'])->where('department', $deptUpper);
                      });
            })->get();

        if ($recipients->count() > 0) {
            Notification::send($recipients, 
                new SubjectUpdatedNotification($subject, $this->isEditMode ? 'updated' : 'created')
            );
        }
    }

    /**
     * ✅ FIXED BULK DUPLICATE METHOD
     */
    public function bulkDuplicate()
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Archive is read only',
                'detail' => 'Switch back to the current semester before duplicating subjects.'
            ]);
            return;
        }

        $count = count($this->selectedSubjects);
        if ($count === 0) return;

        $actor = auth()->user();
        $duplicatedCount = 0;
        $skippedCount = 0;
        $skippedReasons = [];
        $period = $this->activePeriod();

        foreach ($this->selectedSubjects as $id) {
            $original = $this->activeSubjectsQuery()->find($id);
            if (!$original) continue;

            // Validate access
            if (!$this->validateDepartmentAccess($original->department)) {
                $skippedCount++;
                continue;
            }

            $currentSection = strtoupper($original->section ?: 'A');
            
            // Calculate next section
            if ($currentSection === 'Z') {
                $nextSection = 'AA';
            } elseif ($currentSection === 'AA') {
                $nextSection = 'AB';
            } else {
                $nextSection = chr(ord($currentSection) + 1);
            }

            $newEdp = Subject::generateEdpCode(
                $original->major ?: strtok((string) $original->edp_code, '-'),
                (int) $original->year_level,
                $period['school_year'],
                $period['semester']
            );

            // ✅ CRITICAL CHECK: Does this SUBJECT CODE already exist in the NEXT SECTION?
            $subjectExistsInNextSection = $this->activeSubjectsQuery()
                ->where('subject_code', strtoupper($original->subject_code))
                ->where('section', $nextSection)
                ->where('major', $original->major)
                ->where('year_level', $original->year_level)
                ->where('department', $original->department)
                ->exists();
            
            if ($subjectExistsInNextSection) {
                $skippedCount++;
                $skippedReasons[] = "{$original->subject_code} in Section {$nextSection} already exists";
                continue;
            }

            $sectionExists = Subject::edpExistsInWorkspace($newEdp, $period['school_year'], $period['semester']);
            
            if ($sectionExists) {
                $skippedCount++;
                continue;
            }

            // All checks passed, create the new subject
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
            'description' => "Created {$duplicatedCount} new subject sections via bulk duplication." . ($skippedCount > 0 ? " ({$skippedCount} skipped - already exist)" : ""),
        ]);

        $this->reset(['selectedSubjects', 'selectAll']);
        $this->dispatch('subjectUpdated');
        
        if ($skippedCount > 0) {
            $detail = "Duplicated {$duplicatedCount} subjects, {$skippedCount} skipped.";
            if (!empty($skippedReasons)) {
                $detail .= " Reasons: " . implode(", ", array_slice($skippedReasons, 0, 3));
                if (count($skippedReasons) > 3) {
                    $detail .= ", and more...";
                }
            }
            
            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => 'Bulk Duplicate Partial',
                'detail'  => $detail
            ]);
        } else {
            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Bulk Duplicate Complete',
                'detail'  => "{$duplicatedCount} subjects have been copied to the next section."
            ]);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($this->catalogMode !== 'active') {
            $this->selectAll = false;
            $this->selectedSubjects = [];
            return;
        }

        if ($value) {
            $user = auth()->user();
            $userRole = strtolower($user->role ?? '');
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isPowerUser = in_array($userRole, $powerRoles);

            $query = $this->activeSubjectsQuery();

            // 1. Department filter based on role
            if (!$isPowerUser) {
                $query->where('department', $user->department);
            } elseif (!empty($this->selectedDept)) {
                $query->where('department', $this->selectedDept);
            }

            // 2. Section filter
            if (!empty($this->selectedSection)) {
                $query->where('section', $this->selectedSection);
            }

            // 3. Search with closure to prevent filter interference
            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('subject_code', 'like', "%{$this->search}%")
                      ->orWhere('edp_code', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }

            // 4. Year filter
            if (!empty($this->selectedYear)) {
                $query->where('year_level', $this->selectedYear);
            }

            // 5. Major filter
            if (!empty($this->selectedMajor)) {
                $query->where('major', strtoupper($this->selectedMajor));
            }

            $this->selectedSubjects = $query
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->selectedSubjects = [];
        }
    }

    public function deleteSelected()
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Archive is read only',
                'detail' => 'Switch back to the current semester before editing subjects.'
            ]);
            return;
        }

        $count = count($this->selectedSubjects);
        $user = auth()->user();
        
        if ($count > 0) {
            $sampleSubject = $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->first();
            $targetDept = $sampleSubject ? $sampleSubject->department : ($user->department ?? 'General');

            // Validate access
            if (!$this->validateDepartmentAccess($targetDept)) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'Access Denied',
                    'detail' => 'You do not have permission to delete subjects in this department.'
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
                ->where(function($query) use ($targetDept) {
                    $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                          ->orWhere(function($q) use ($targetDept) {
                              $q->whereIn('role', ['dean', 'oic'])
                                ->where('department', $targetDept);
                          });
                })->get();

            if ($recipients->count() > 0) {
                Notification::send($recipients, new SubjectUpdatedNotification(
                    (object)[
                        'subject_code' => 'BATCH PURGE', 
                        'subject_description' => "{$count} Records removed from {$targetDept}"
                    ], 
                    'deleted' 
                ));
            }

            $this->dispatch('notify', [
                'type'        => 'error', 
                'title'       => 'REGISTRY PURGED', 
                'message'     => "Successfully removed {$count} subjects from the database.",
                'sender_name' => $user->name
            ]);

            $this->dispatch('toast', [
                'type'    => 'success', 
                'message' => 'Batch Deleted', 
                'detail'  => "{$count} subjects successfully removed."
            ]);

            $this->dispatch('subjectUpdated');
            $this->reset(['selectedSubjects', 'selectAll']);
        }
    }

    public function deleteSubject($id)
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Archive is read only',
                'detail' => 'Archived subjects cannot be edited or deleted from this view.'
            ]);
            return;
        }

        $user = auth()->user();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);

        // Validate access
        if (!$this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Access Denied',
                'detail' => 'You do not have permission to delete subjects in this department.'
            ]);
            return;
        }

        $subjectCode = $subject->subject_code;
        $subjectDesc = $subject->description;
        $targetDept = $subject->department;

        $recipients = User::where('id', '!=', $user->id)
            ->where(function($query) use ($targetDept) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                      ->orWhere(function($q) use ($targetDept) {
                          $q->whereIn('role', ['dean', 'oic'])
                            ->where('department', $targetDept);
                      });
            })->get();

        if ($recipients->count() > 0) {
            Notification::send($recipients, new SubjectUpdatedNotification(
                $subject, 
                'deleted'
            ));
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
            'sender_name' => $user->name
        ]);

        $this->dispatch('toast', [
            'type'    => 'warning', 
            'message' => 'Subject Deleted', 
            'detail'  => "{$subjectCode} - {$subjectDesc} removed."
        ]);

        $this->dispatch('subjectUpdated');
    }

    public function mount() {
        $user = auth()->user();
        $userRole = strtolower($user->role);
        $powerRoles = ['admin', 'registrar', 'associate_dean'];

        if (!in_array($userRole, $powerRoles)) {
            $this->selectedDept = $user->department;
        } else {
            $this->selectedDept = '';
        }
    }

    /**
     * FIXED RENDER METHOD — scoped to active (non-archived) subjects only
     */
    public function render()
    {
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        $period = $this->activePeriod();
        $archiveOptions = $this->archiveOptions();
        $isArchiveMode = $this->catalogMode === 'archive';

        $query = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') {
            $query->whereRaw('1 = 0');
        }

        // 1. DEPARTMENT FILTER - ROLE-BASED SECURITY
        if (!$isPowerUser) {
            $query->where('department', $user->department);
        } elseif (!empty($this->selectedDept)) {
            $query->where('department', $this->selectedDept);
        }

        // 2. SECTION FILTER
        if (!empty($this->selectedSection)) {
            $query->where('section', $this->selectedSection);
        }

        // 3. YEAR LEVEL FILTER
        if (!empty($this->selectedYear)) {
            $query->where('year_level', (int)$this->selectedYear);
        }

        // 4. MAJOR FILTER
        if (!empty($this->selectedMajor)) {
            $query->where('major', strtoupper($this->selectedMajor));
        }

        // 5. SEARCH FILTER - WRAPPED IN CLOSURE
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('subject_code', 'like', "%{$this->search}%")
                  ->orWhere('edp_code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $subjects = $query->orderBy('edp_code', 'asc')->paginate(10);

        // DYNAMIC SECTIONS DROPDOWN — scoped by department/role as usual.
        $sectionsQuery = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') {
            $sectionsQuery->whereRaw('1 = 0');
        }

        if (!$isPowerUser) {
            $sectionsQuery->where('department', $user->department);
        } elseif ($isPowerUser && !empty($this->selectedDept)) {
            $sectionsQuery->where('department', $this->selectedDept);
        }

        $sections = $sectionsQuery
            ->distinct()
            ->pluck('section')
            ->filter()
            ->sort()
            ->values();

        return view('livewire.manage-subjects', [
            'subjects' => $subjects,
            'availableMajors' => $this->getAvailableMajorsProperty(),
            'sections' => $sections,
            'activities' => \App\Models\Activity::with('user')
                ->latest()
                ->take(10)
                ->get(),
            'isPowerUser' => $isPowerUser,
            'activePeriod' => $period,
            'catalogMode' => $this->catalogMode,
            'archiveOptions' => $archiveOptions,
            'isArchiveMode' => $isArchiveMode,
        ]); 
    }
}
