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
     * Generate EDP Code: MAJOR-YEAR[LEVEL]###
     * Example: IT-261001 (IT = major, 26 = 2026, 1 = 1st year, 001 = sequence)
     */
    private function generateEdpCode()
    {
        if (empty($this->major) || empty($this->year_level) || empty($this->department)) {
            $this->edp_code = '';
            return;
        }

        $major = strtoupper($this->major);
        $currentYear = date('y'); // e.g., "26" for 2026
        $yearLevel = (int)$this->year_level;

        // Find the highest sequence number for this major+year+level combination
        $lastSubject = Subject::where('major', $major)
            ->where('year_level', $yearLevel)
            ->orderBy('edp_code', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastSubject) {
            // Extract sequence from edp_code (last 3 digits)
            $parts = explode('-', $lastSubject->edp_code);
            if (count($parts) >= 2) {
                $lastCode = $parts[1]; // e.g., "261001"
                $lastSequence = (int)substr($lastCode, -3); // Get last 3 digits
                $nextSequence = $lastSequence + 1;
            }
        }

        // Format: MAJOR-YEAR[LEVEL]### (e.g., IT-261001)
        $this->edp_code = "{$major}-{$currentYear}{$yearLevel}" . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
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

        $query = Subject::where('subject_code', $subjectCodeUpper)
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
        $this->previewData = collect(array_slice($data, 0, 10))->map(function ($row) use ($indexes) {
            $edp = strtoupper(trim($row[$indexes['edp_code']] ?? ''));
            $exists = Subject::where('edp_code', $edp)->exists();
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

            $edpCode = strtoupper($value('edp_code'));
            $section = strtoupper($value('section', 'A'));

            // Check if subject already exists
            $exists = Subject::where('edp_code', $edpCode)
                ->where('section', $section)
                ->exists();

            if ($exists) {
                \Log::warning("Subject {$edpCode} Section {$section} already exists. Skipping import.");
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
        $this->resetValidation();
        $subject = Subject::findOrFail($id);

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

        // Validation
        $this->validate([
            'edp_code'       => 'required|unique:subjects,edp_code,' . ($this->subjectId ?? 'NULL') . ',id',
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

        // Check for duplicate EDP+Section if creating new
        if (!$this->isEditMode) {
            $duplicate = Subject::where('edp_code', $edpUpper)
                ->where('section', $sectionUpper)
                ->first();

            if ($duplicate) {
                $this->showDuplicateConfirmModal = true;
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
        $count = count($this->selectedSubjects);
        if ($count === 0) return;

        $actor = auth()->user();
        $duplicatedCount = 0;
        $skippedCount = 0;
        $skippedReasons = [];

        foreach ($this->selectedSubjects as $id) {
            $original = Subject::find($id);
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

            // Extract major, year, level from original EDP
            $edpParts = explode('-', $original->edp_code);
            
            if (count($edpParts) < 2) {
                \Log::error("Invalid EDP format for subject {$original->id}: {$original->edp_code}");
                $skippedCount++;
                continue;
            }

            $majorCode = $edpParts[0];
            $yearCodePart = $edpParts[1];
            
            $year = substr($yearCodePart, 0, 2);
            $level = substr($yearCodePart, 2, 1);
            $oldSequence = (int)substr($yearCodePart, 3, 3);

            $nextSequence = $oldSequence + 1;
            $maxAttempts = 100;
            $attempts = 0;
            
            while ($attempts < $maxAttempts) {
                $newSequenceStr = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
                $newEdp = "{$majorCode}-{$year}{$level}{$newSequenceStr}";
                
                $edpExists = Subject::where('edp_code', $newEdp)->exists();
                
                if (!$edpExists) {
                    break;
                }
                
                $nextSequence++;
                $attempts++;
            }

            if ($attempts >= $maxAttempts) {
                \Log::error("Could not find available EDP sequence for {$majorCode}-{$year}{$level}");
                $skippedCount++;
                continue;
            }

            // ✅ CRITICAL CHECK: Does this SUBJECT CODE already exist in the NEXT SECTION?
            $subjectExistsInNextSection = Subject::where('subject_code', strtoupper($original->subject_code))
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

            // Also check if this EDP+SECTION combination exists
            $sectionExists = Subject::where('edp_code', $newEdp)
                ->where('section', $nextSection)
                ->exists();
            
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
        if ($value) {
            $user = auth()->user();
            $userRole = strtolower($user->role ?? '');
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isPowerUser = in_array($userRole, $powerRoles);

            $query = Subject::query();

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
        $count = count($this->selectedSubjects);
        $user = auth()->user();
        
        if ($count > 0) {
            $sampleSubject = Subject::whereIn('id', $this->selectedSubjects)->first();
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

            Subject::whereIn('id', $this->selectedSubjects)->delete();

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
        $user = auth()->user();
        $subject = Subject::findOrFail($id);

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
     * FIXED RENDER METHOD
     */
    public function render()
    {
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        $query = Subject::query();

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

        // DYNAMIC SECTIONS DROPDOWN
        $sectionsQuery = Subject::query();

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
        ]); 
    }
}
