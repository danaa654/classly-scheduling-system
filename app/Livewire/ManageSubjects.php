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

    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedDept($value)
    {
        $this->selectedMajor = '';
        $this->resetPage();
    }

    public function updatedSelectedYear()
    {
        $this->resetPage();
    }

    public function updatedSelectedMajor()
    {
        $this->resetPage();
    }

    public function updatedSelectedSection()
    {
        $this->resetPage();
    }
    
    public function updatedImportFile()
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);

        // Small delay for that professional "Scanning" feel in the UI
        usleep(500000); 

        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headerRow = array_shift($data);
        
        // Normalize headers for strict comparison
        $normalizedHeader = array_map(fn($h) => strtolower(trim($h)), $headerRow);

        // 1. FINGERPRINT DETECTION (Security check for wrong file types)
        if (in_array('room_name', $normalizedHeader) || in_array('building', $normalizedHeader)) {
            $this->abortImport('Room Registry');
            return;
        }

        if (in_array('faculty_name', $normalizedHeader) || in_array('employee_id', $normalizedHeader)) {
            $this->abortImport('Faculty Directory');
            return;
        }

        // 2. STRICT SUBJECT HEADER VALIDATION
        $expected = ['edp_code', 'subject_code', 'section', 'description', 'units', 'department', 'duration_hours', 'type', 'meetings_per_week'];
        
        if ($normalizedHeader !== $expected) {
            $this->abortImport('Invalid Subject Template');
            return;
        }

        // 3. SUCCESS: Preview the data
        $this->previewData = collect(array_slice($data, 0, 10))->map(function ($row) {
            $edp = strtoupper(trim($row[0] ?? ''));
            $exists = \App\Models\Subject::where('edp_code', $edp)->exists();
            return [
                'edp_code' => $edp,
                'subject' => $row[1] ?? '',
                'exists' => $exists
            ];
        })->toArray();
    }

    /**
     * Helper to reset and toast on mismatch.
     */
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
     * FINALIZE IMPORT
     */
    public function importSubjects()
    {
        if (!$this->importFile) return;

        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        $header = true;
        $count = 0;
        $detectedDept = null; 
        $actor = auth()->user();

        $userRole = strtolower($actor->role);
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            if ($header) { $header = false; continue; }
            if (empty($row[0])) continue;

            // Column Mapping: 0: edp_code, 1: subject_code, 2: section, 3: description, 
            // 4: units, 5: department, 6: duration_hours, 7: type, 8: meetings_per_week
            $rowDept    = strtoupper(trim($row[5] ?? ''));
            $rowSection = strtoupper(trim($row[2] ?? 'A'));
            
            // Authorization check
            if (!$isPowerUser && $rowDept !== strtoupper($actor->department)) {
                continue; 
            }

            if (!$detectedDept && !empty($rowDept)) {
                $detectedDept = $rowDept;
            }

            $rawDuration = trim($row[6] ?? '3');
            $rawType     = trim($row[7] ?? 'Major');
            $rawMeetings = (int)($row[8] ?? 1);
            $rawUnits = (int)$row[4];
            $clampedUnits = max(3, min(5, $rawUnits));

            $edpCode = strtoupper(trim($row[0]));
            $section = strtoupper(trim($row[2] ?? 'A'));

            // Check if subject already exists
            $exists = \App\Models\Subject::where('edp_code', $edpCode)
                ->where('section', $section)
                ->exists();

            if ($exists) {
                \Log::warning("Subject {$edpCode} Section {$section} already exists. Skipping import.");
                continue;
            }

            // Normalize type to 'Major' or 'Minor'
            $normalizedType = in_array(ucfirst(strtolower($rawType)), ['Major', 'Minor']) 
                ? ucfirst(strtolower($rawType)) 
                : 'Major';

            \App\Models\Subject::create([
                'edp_code'       => $edpCode,
                'subject_code'   => strtoupper(trim($row[1])),
                'section'        => $section,
                'description'    => trim($row[3]),
                'units'          => $clampedUnits,
                'department'     => $rowDept,
                'duration_hours' => (float) filter_var($rawDuration, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 3,
                'meetings_per_week' => $rawMeetings,
                'type'           => $normalizedType,
            ]);
            $count++;
        }
        fclose($file);

        if ($count > 0) {
            $targetDept = !empty($this->selectedDept) ? $this->selectedDept : ($detectedDept ?? $actor->department ?? 'General');

            \App\Models\Activity::create([
                'user_id'     => $actor->id,
                'action'      => 'Import',
                'module'      => 'Subjects',
                'description' => "Successfully batch-imported {$count} subjects into the {$targetDept} department.",
            ]);

            $recipients = \App\Models\User::where('id', '!=', $actor->id)
                ->where(function($query) use ($targetDept) {
                    $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                          ->orWhere(function($q) use ($targetDept) {
                              $q->whereIn('role', ['dean', 'oic'])
                                ->where('department', $targetDept);
                          });
                })->get();

            if ($recipients->count() > 0) {
                \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\SubjectUpdatedNotification(
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

    public function openModal()
    {
        $this->resetValidation();
        $this->resetExcept(['selectedDept', 'search', 'selectedYear', 'selectedMajor']);
        $this->isEditMode = false;
        $this->units = 3;
        $this->type = 'Major';
        $this->duration_hours = 3;
        $this->meetings_per_week = 1;

        $user = auth()->user();
        $userRole = strtolower($user->role);
        $powerRoles = ['admin', 'registrar', 'associate_dean'];

        if (!in_array($userRole, $powerRoles)) {
            $this->department = $user->department;
        }

        $this->showModal = true;
    }

    public function editSubject($id)
    {
        $this->resetValidation();
        $this->isEditMode = true;
        $subject = Subject::findOrFail($id);
        
        $this->subjectId = $subject->id;
        $this->edp_code = $subject->edp_code;
        $this->subject_code = $subject->subject_code;
        $this->section = $subject->section;
        $this->description = $subject->description;
        $this->units = $subject->units; // ✅ Load actual units from database
        $this->type = $subject->type ?? 'Major'; // ✅ Load actual type from database
        $this->duration_hours = $subject->duration_hours ?? 3;
        $this->meetings_per_week = $subject->meetings_per_week ?? 1; 
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

    public function getEdpMajorMismatchProperty()
    {
        if (empty($this->edp_code) || empty($this->subject_code)) {
            return false;
        }

        $edpParts = explode('-', strtoupper($this->edp_code));
        $edpMajor = $edpParts[1] ?? '';
        $subjectCodeUpper = strtoupper($this->subject_code);

        return !str_starts_with($subjectCodeUpper, $edpMajor);
    }

    // Available majors mapping
    private $majorsByDept = [
        'CCS' => ['IT' => 'Information Technology', 'ACT' => 'Accounting'],
        'SHTM' => ['HM' => 'Hospitality', 'TM' => 'Tourism'],
        'COC' => ['FB' => 'Forensic Biology', 'LD' => 'Lie Detection', 'QD' => 'Questioned Documents'],
        'CTE' => ['ED' => 'Education'],
    ];

    public $selectedMajorCode = '';

    public function updatedSelectedMajorCode($value)
    {
        if (empty($value) || empty($this->department)) {
            $this->edp_code = '';
            $this->subject_code = '';
            return;
        }

        $deptUpper = strtoupper($this->department);
        $majorUpper = strtoupper($value);

        $this->edp_code = "{$deptUpper}-{$majorUpper}-";
        $this->subject_code = "{$majorUpper}";
    }

    public function updatedDepartment($value)
    {
        $this->selectedMajorCode = '';
        $this->edp_code = '';
        $this->subject_code = '';
    }

    public function getAvailableMajorsProperty()
    {
        if (empty($this->department)) {
            return [];
        }

        $dept = strtoupper($this->department);
        return $this->majorsByDept[$dept] ?? [];
    }

    public function updatedEdpCode($value)
    {
        $value = strtoupper(trim($value));
        if (!empty($this->selectedMajorCode)) {
            $major = strtoupper($this->selectedMajorCode);
            if (preg_match('/^[A-Z]+-[A-Z]+-(\d+)/', $value, $matches)) {
                $number = $matches[1];
                $this->subject_code = "{$major}{$number}";
            }
        }
    }

    public function updatedSubjectCode($value)
    {
        $value = strtoupper(trim($value));
        if (!empty($this->selectedMajorCode) && !empty($this->department)) {
            $dept = strtoupper($this->department);
            $major = strtoupper($this->selectedMajorCode);
            if (preg_match("/^{$major}(\d+)/i", $value, $matches)) {
                $number = $matches[1];
                $section = strtoupper($this->section ?: 'A');
                $this->edp_code = "{$dept}-{$major}-{$number}-{$section}";
            }
        }
    }

    public $showDuplicateModal = false;

    public function saveSubject()
    {
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        if (!$isPowerUser) {
            $this->department = $user->department;
        }

        $edpUpper = strtoupper($this->edp_code);
        $sectionUpper = strtoupper($this->section);
        $deptUpper = strtoupper($this->department);
        $subjectCodeUpper = strtoupper($this->subject_code);

        $this->validate([
            'edp_code'       => 'required',
            'subject_code'   => 'required',
            'section'        => 'required|max:10',
            'department'     => 'required',
        ]);

        $edpParts = explode('-', $edpUpper);
        
        if (count($edpParts) < 3) {
            $this->addError('edp_code', "Invalid EDP Code format. Expected: DEPT-MAJOR-YEAR-SECTION");
            return;
        }

        $edpDept = $edpParts[0];
        $edpMajor = $edpParts[1];

        if ($edpDept !== $deptUpper) {
            $this->addError('edp_code', "Mismatch: EDP Code '{$edpUpper}' belongs to {$edpDept}, not {$deptUpper}.");
            return; 
        }

        if (!str_starts_with($subjectCodeUpper, $edpMajor)) {
            $this->addError('subject_code', "Mismatch: Subject Code '{$subjectCodeUpper}' should start with {$edpMajor} to match EDP Code '{$edpUpper}'.");
            return;
        }

        if (!$this->isEditMode) {
            $existingSections = \App\Models\Subject::where('subject_code', $subjectCodeUpper)
                ->pluck('section')
                ->map(function($sec) {
                    return strtoupper($sec);
                })
                ->sort()
                ->values()
                ->toArray();

            if (!empty($existingSections)) {
                $validSections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
                
                $lastSection = end($existingSections);
                $lastIndex = array_search($lastSection, $validSections);
                
                if ($lastIndex !== false) {
                    $expectedNextSection = $validSections[$lastIndex + 1] ?? null;
                    
                    if ($expectedNextSection && $sectionUpper !== $expectedNextSection) {
                        $this->addError('section', "Invalid section '{$sectionUpper}'. Existing sections: " . implode(', ', $existingSections) . ". Next section must be '{$expectedNextSection}'.");
                        return;
                    }
                }
            }
        }

        $duplicate = \App\Models\Subject::where('edp_code', $edpUpper)
            ->where('section', $sectionUpper)
            ->when($this->isEditMode, function($q) {
                $q->where('id', '!=', $this->subjectId);
            })
            ->first();

        if ($duplicate && !$this->isEditMode) {
            $this->showDuplicateModal = true;
            return;
        }

        $this->executeSave();
    }

    public function executeSave()
    {
        $user = auth()->user();
        $deptUpper = strtoupper($this->department);
        $edpUpper = strtoupper($this->edp_code);
        $subjectCodeUpper = strtoupper($this->subject_code);

        $edpParts = explode('-', $edpUpper);
        $edpDept = $edpParts[0];
        $edpMajor = $edpParts[1] ?? '';

        if ($edpDept !== $deptUpper) {
            $this->addError('edp_code', "Mismatch: EDP Code belongs to {$edpDept}, not {$deptUpper}.");
            return;
        }

        if (!str_starts_with($subjectCodeUpper, $edpMajor)) {
            $this->addError('subject_code', "Subject Code should start with {$edpMajor}.");
            return;
        }

        $this->validate([
            'edp_code'       => 'required',
            'subject_code'   => 'required',
            'section'        => 'required|max:10',
            'description'    => 'required',
            'department'     => 'required',
            'units'          => 'required|integer|min:3|max:5',
            'type'           => 'required|in:Major,Minor',
            'duration_hours' => 'required|numeric|min:1|max:10',
            'meetings_per_week' => 'required|integer|min:1|max:5',
        ]);

        // ✅ Normalize type to ensure proper casing
        $normalizedType = in_array($this->type, ['Major', 'Minor']) 
            ? $this->type 
            : 'Major';

        $subject = \App\Models\Subject::updateOrCreate(
            ['id' => $this->subjectId],
            [
                'edp_code'       => $edpUpper,
                'subject_code'   => $subjectCodeUpper,
                'section'        => strtoupper($this->section),
                'description'    => $this->description,
                'units'          => $this->units,
                'type'           => $normalizedType,
                'duration_hours' => $this->duration_hours,
                'meetings_per_week' => $this->meetings_per_week,
                'department'     => $deptUpper,
            ]
        );

        $this->logActivityAndNotify($subject, $user, $deptUpper);

        $this->showDuplicateModal = false;
        $this->showModal = false;

        $this->dispatch('toast', [
            'type'    => 'success', 
            'message' => $this->isEditMode ? 'Subject Updated' : 'Subject Created', 
            'detail'  => "{$subject->subject_code} is now synchronized."
        ]);

        $this->dispatch('subjectUpdated');
        $this->reset(['edp_code', 'subject_code', 'section', 'description', 'units', 'type', 'duration_hours', 'department', 'subjectId']);
    }

    private function logActivityAndNotify($subject, $user, $deptUpper)
    {
        \App\Models\Activity::create([
            'user_id'     => $user->id,
            'action'      => $this->isEditMode ? 'Update' : 'Add',
            'module'      => 'Subjects',
            'description' => $this->isEditMode 
                ? "Updated {$subject->subject_code} in {$deptUpper}."
                : "Manually added {$subject->subject_code} to {$deptUpper}.",
        ]);

        $recipients = \App\Models\User::where('id', '!=', $user->id)
            ->where(function($query) use ($deptUpper) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                      ->orWhere(function($q) use ($deptUpper) {
                          $q->whereIn('role', ['dean', 'oic'])->where('department', $deptUpper);
                      });
            })->get();

        if ($recipients->count() > 0) {
            \Illuminate\Support\Facades\Notification::send($recipients, 
                new \App\Notifications\SubjectUpdatedNotification($subject, $this->isEditMode ? 'updated' : 'created')
            );
        }
    }

    public function confirmDuplicateSubject()
    {
        if (!$this->duplicateCandidateId) {
            return;
        }

        $original = \App\Models\Subject::findOrFail($this->duplicateCandidateId);

        $this->subjectId = null;
        $this->isEditMode = false;

        $this->subject_code = $original->subject_code;
        $this->description = $original->description;
        $this->units = $original->units;
        $this->type = $original->type ?? 'Major'; // ✅ Preserve type
        $this->duration_hours = $original->duration_hours;
        $this->meetings_per_week = $original->meetings_per_week ?? 1;
        $this->department = $original->department;

        $currentSection = strtoupper($original->section ?: 'A');

        if ($currentSection === 'Z') {
            $nextSection = 'AA';
        } elseif ($currentSection === 'AA') {
            $nextSection = 'AB';
        } else {
            $nextSection = chr(ord($currentSection) + 1);
        }

        $this->section = $nextSection;

        $parts = explode('-', $original->edp_code);

        if (count($parts) >= 4) {
            array_pop($parts);
            $this->edp_code = implode('-', $parts) . '-' . $nextSection;
        } else {
            $this->edp_code = $original->edp_code . '-' . $nextSection;
        }

        $this->showDuplicateConfirmModal = false;
        $this->duplicateCandidateId = null;

        $this->showModal = true;
    }

    public function bulkDuplicate()
    {
        $count = count($this->selectedSubjects);
        if ($count === 0) return;

        $actor = auth()->user();
        $duplicatedCount = 0;
        $skippedCount = 0;

        foreach ($this->selectedSubjects as $id) {
            $original = \App\Models\Subject::find($id);
            if (!$original) continue;

            $currentSection = strtoupper($original->section ?: 'A');
            
            if ($currentSection === 'Z') {
                $nextSection = 'AA';
            } elseif ($currentSection === 'AA') {
                $nextSection = 'AB';
            } else {
                $nextSection = chr(ord($currentSection) + 1);
            }

            $parts = explode('-', $original->edp_code);
            $newEdp = $original->edp_code;
            
            if (count($parts) >= 4) {
                array_pop($parts);
                $newEdp = implode('-', $parts) . '-' . $nextSection;
            } else {
                $newEdp = $original->edp_code . '-' . $nextSection;
            }

            $exists = \App\Models\Subject::where('edp_code', $newEdp)->exists();
            
            if ($exists) {
                $skippedCount++;
                continue;
            }

            \App\Models\Subject::create([
                'edp_code'       => $newEdp,
                'subject_code'   => $original->subject_code,
                'section'        => $nextSection,
                'description'    => $original->description,
                'units'          => $original->units,
                'department'     => $original->department,
                'type'           => $original->type ?? 'Major', // ✅ Preserve type
                'duration_hours' => $original->duration_hours,
                'meetings_per_week' => $original->meetings_per_week ?? 1,
            ]);

            $duplicatedCount++;
        }

        \App\Models\Activity::create([
            'user_id'     => $actor->id,
            'action'      => 'Bulk Duplicate',
            'module'      => 'Subjects',
            'description' => "Created {$duplicatedCount} new subject sections via bulk duplication." . ($skippedCount > 0 ? " ({$skippedCount} skipped - already exist)" : ""),
        ]);

        $this->reset(['selectedSubjects', 'selectAll']);
        $this->dispatch('subjectUpdated');
        
        if ($skippedCount > 0) {
            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => 'Bulk Duplicate Partial',
                'detail'  => "{$duplicatedCount} subjects duplicated, {$skippedCount} skipped (already exist)."
            ]);
        } else {
            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Bulk Duplicate Complete',
                'detail'  => "{$duplicatedCount} subjects have been copied to the next section."
            ]);
        }
    }

    public function updatedSection($value)
    {
        $newSection = strtoupper(trim($value));
        if (!empty($this->edp_code)) {
            $parts = explode('-', strtoupper($this->edp_code));
            if (count($parts) >= 3) {
                $base = implode('-', array_slice($parts, 0, 3));
                $this->edp_code = $base . '-' . $newSection;
            }
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $user = auth()->user();
            $userRole = strtolower($user->role ?? '');
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isPowerUser = in_array($userRole, $powerRoles);

            $query = \App\Models\Subject::query();

            $query->where(function($q) use ($user, $isPowerUser) {
                if (!$isPowerUser) {
                    $q->where('department', $user->department);
                } elseif (!empty($this->selectedDept)) {
                    $q->where('department', $this->selectedDept);
                }
            });

            if (!empty($this->selectedSection)) {
                $query->where('section', $this->selectedSection);
            }

            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('subject_code', 'like', "%{$this->search}%")
                      ->orWhere('edp_code', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }

            if (!empty($this->selectedYear)) {
                $yearNumber = $this->selectedYear;
                $query->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(edp_code, '-', 3), '-', -1) LIKE ?", ["{$yearNumber}%"]);
            }

            if (!empty($this->selectedMajor)) {
                $major = strtoupper($this->selectedMajor);
                $query->where(function($q) use ($major) {
                    $q->where('subject_code', 'like', $major . '%')
                      ->orWhere('edp_code', 'like', "%-{$major}-%");
                });
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
            $sampleSubject = \App\Models\Subject::whereIn('id', $this->selectedSubjects)->first();
            $targetDept = $sampleSubject ? $sampleSubject->department : ($user->department ?? 'General');

            \App\Models\Subject::whereIn('id', $this->selectedSubjects)->delete();

            \App\Models\Activity::create([
                'user_id'     => $user->id,
                'action'      => 'Delete',
                'module'      => 'Subjects',
                'description' => "Bulk removed {$count} subjects from the {$targetDept} catalog.",
            ]);

            $recipients = \App\Models\User::where('id', '!=', $user->id)
                ->where(function($query) use ($targetDept) {
                    $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                          ->orWhere(function($q) use ($targetDept) {
                              $q->whereIn('role', ['dean', 'oic'])
                                ->where('department', $targetDept);
                          });
                })->get();

            if ($recipients->count() > 0) {
                \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\SubjectUpdatedNotification(
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
        $subject = \App\Models\Subject::findOrFail($id);
        $subjectCode = $subject->subject_code;
        $subjectDesc = $subject->description;
        $targetDept = $subject->department;

        $recipients = \App\Models\User::where('id', '!=', $user->id)
            ->where(function($query) use ($targetDept) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                      ->orWhere(function($q) use ($targetDept) {
                          $q->whereIn('role', ['dean', 'oic'])
                            ->where('department', $targetDept);
                      });
            })->get();

        if ($recipients->count() > 0) {
            \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\SubjectUpdatedNotification(
                $subject, 
                'deleted'
            ));
        }

        $subject->delete();

        \App\Models\Activity::create([
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
        if (!in_array(strtolower(auth()->user()->role), ['admin', 'registrar', 'associate_dean'])) {
            $this->selectedDept = auth()->user()->department;
        } else {
            $this->selectedDept = '';
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $userRole = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);

        $query = \App\Models\Subject::query();

        $query->where(function($q) use ($user, $isPowerUser) {
            if (!$isPowerUser) {
                $q->where('department', $user->department);
            } else {
                if (!empty($this->selectedDept)) {
                    $q->where('department', $this->selectedDept);
                }
            }
        });

        if (!empty($this->selectedSection)) {
            $query->where('section', $this->selectedSection);
        }

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('subject_code', 'like', "%{$this->search}%")
                  ->orWhere('edp_code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if (!empty($this->selectedYear)) {
            $yearNumber = $this->selectedYear;
            $query->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(edp_code, '-', 3), '-', -1) LIKE ?", ["{$yearNumber}%"]);
        }

        if (!empty($this->selectedMajor)) {
            $major = strtoupper($this->selectedMajor);
            $query->where(function($q) use ($major) {
                $q->where('subject_code', 'like', $major . '%')
                  ->orWhere('edp_code', 'like', "%-{$major}-%");
            });
        }

        $subjects = $query->orderBy('edp_code', 'asc')->paginate(10);

        return view('livewire.manage-subjects', [
            'subjects' => $subjects,

            'activities' => \App\Models\Activity::with('user')
                ->latest()
                ->take(10)
                ->get(),

            'sections' => \App\Models\Subject::query()
                ->when(!$isPowerUser, function($q) use ($user) {
                    $q->where('department', $user->department);
                })
                ->when(!empty($this->selectedDept) && $isPowerUser, function($q) {
                    $q->where('department', $this->selectedDept);
                })
                ->distinct()
                ->pluck('section')
                ->filter()
                ->sort()
                ->values()
        ]); 
    }
}