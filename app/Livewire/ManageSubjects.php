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
    // If we see 'room_name', we know they accidentally grabbed the Room CSV
    if (in_array('room_name', $normalizedHeader) || in_array('building', $normalizedHeader)) {
        $this->abortImport('Room Registry');
        return;
    }

    // If we see 'faculty_name', they grabbed the Faculty CSV
    if (in_array('faculty_name', $normalizedHeader) || in_array('employee_id', $normalizedHeader)) {
        $this->abortImport('Faculty Directory');
        return;
    }

    // 2. STRICT SUBJECT HEADER VALIDATION
    // These must match your Subject database schema exactly
    $expected = ['edp_code', 'subject_code', 'section', 'description', 'units', 'department', 'duration_hours', 'type'];
    
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
 * Rewritten to be specific to the Subject Module.
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

        // --- Column Mapping based on Template ---
        // 0: edp_code, 1: subject_code, 2: section, 3: description, 
        // 4: units, 5: department, 6: duration, 7: type
        $rowDept    = strtoupper(trim($row[5] ?? ''));
        $rowSection = strtoupper(trim($row[2] ?? 'A'));
        
        // Authorization check: Deans/OICs can only import their own department
        if (!$isPowerUser && $rowDept !== strtoupper($actor->department)) {
            continue; 
        }

        // Detect the department for notification targeting
        if (!$detectedDept && !empty($rowDept)) {
            $detectedDept = $rowDept;
        }

        $rawDuration = trim($row[6] ?? '3hrs');
        $rawType     = trim($row[7] ?? 'Major');
        $rawUnits = (int)$row[4];
        $clampedUnits = max(3, min(5, $rawUnits));

        $edpCode = strtoupper(trim($row[0]));
$section = strtoupper(trim($row[2] ?? 'A'));

// Check if subject already exists
$exists = \App\Models\Subject::where('edp_code', $edpCode)
    ->where('section', $section)
    ->exists();

if ($exists) {
    // Log warning but continue with next row
    \Log::warning("Subject {$edpCode} Section {$section} already exists. Skipping import.");
    continue;
}

\App\Models\Subject::create([
    'edp_code'       => $edpCode,
    'subject_code'   => strtoupper(trim($row[1])),
    'section'        => $section,
    'description'    => trim($row[3]),
    'units'          => $clampedUnits,
    'department'     => $rowDept,
    'duration_hours' => (int) filter_var($rawDuration, FILTER_SANITIZE_NUMBER_INT) ?: 3,
    'type'           => ucfirst(strtolower($rawType)),
]);
        $count++;
    }
    fclose($file);

    if ($count > 0) {
        $targetDept = !empty($this->selectedDept) ? $this->selectedDept : ($detectedDept ?? $actor->department ?? 'General');

        // 1. Sidebar Activity Log
        \App\Models\Activity::create([
            'user_id'     => $actor->id,
            'action'      => 'Import',
            'module'      => 'Subjects',
            'description' => "Successfully batch-imported {$count} subjects into the {$targetDept} department.",
        ]);

        // 2. SMART SCOPED NOTIFICATION (Hierarchical Flow)
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

        // 3. UI FEEDBACK: Global Slide-in
        $this->dispatch('notify', [
            'type'        => 'success', 
            'title'       => 'CATALOG SYNCED',
            'message'     => "Successfully batch-imported {$count} subjects.",
            'sender_name' => $actor->name
        ]);

        // 4. REFRESH: Sync UI components
        $this->dispatch('subjectUpdated');

        // 5. Local Toast
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

    $user = auth()->user();
    $userRole = strtolower($user->role);
    $powerRoles = ['admin', 'registrar', 'associate_dean'];

    // If NOT a power user, pre-fill and lock the department
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
        $this->units = 3;
        $this->type = $subject->type ?? 'Major';
        $this->duration_hours = $subject->duration_hours ?? 3;
        $this->department = $subject->department;
        
        $this->showModal = true;
    }

    public function updatedUnits($value)
{
    // Ensure it's a number; if empty or non-numeric, default to 3
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

public $selectedMajorCode = ''; // New property for major selection

// Listen for major changes
public function updatedSelectedMajorCode($value)
{
    if (empty($value) || empty($this->department)) {
        $this->edp_code = '';
        $this->subject_code = '';
        return;
    }

    $deptUpper = strtoupper($this->department);
    $majorUpper = strtoupper($value);

    // Auto-populate EDP and Subject code prefixes
    $this->edp_code = "{$deptUpper}-{$majorUpper}-";
    $this->subject_code = "{$majorUpper}";
}

public function updatedDepartment($value)
{
    // Reset major when department changes
    $this->selectedMajorCode = '';
    $this->edp_code = '';
    $this->subject_code = '';
}

// Get majors for current department
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
    // Auto-update subject_code if major and numeric part are detectable
    if (!empty($this->selectedMajorCode)) {
        $major = strtoupper($this->selectedMajorCode);
        // Match pattern like CCS-ACT-101-C or CCS-ACT-101
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
        // Extract numeric part from subject code (e.g., ACT101 → 101)
        if (preg_match("/^{$major}(\d+)/i", $value, $matches)) {
            $number = $matches[1];
            // If section exists, include it; otherwise default to A
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

    // 1. Enforce Department for non-power users
    if (!$isPowerUser) {
        $this->department = $user->department;
    }

    $edpUpper = strtoupper($this->edp_code);
    $sectionUpper = strtoupper($this->section);
    $deptUpper = strtoupper($this->department);
    $subjectCodeUpper = strtoupper($this->subject_code);

    // 2. Initial Validation
    $this->validate([
        'edp_code'       => 'required',
        'subject_code'   => 'required',
        'section'        => 'required|max:10',
        'department'     => 'required',
    ]);

    // 3. Extract department and major from EDP code
    // Format: CCS-ACT-101-B -> [CCS, ACT, 101, B]
    $edpParts = explode('-', $edpUpper);
    
    if (count($edpParts) < 3) {
        $this->addError('edp_code', "Invalid EDP Code format. Expected: DEPT-MAJOR-YEAR-SECTION");
        return;
    }

    $edpDept = $edpParts[0];
    $edpMajor = $edpParts[1];

    // 4. Department-EDP Mismatch Check
    if ($edpDept !== $deptUpper) {
        $this->addError('edp_code', "Mismatch: EDP Code '{$edpUpper}' belongs to {$edpDept}, not {$deptUpper}.");
        return; 
    }

    // 5. Subject Code-Major Mismatch Check
    // Subject code should start with the major from EDP (e.g., ACT101 starts with ACT)
    if (!str_starts_with($subjectCodeUpper, $edpMajor)) {
        $this->addError('subject_code', "Mismatch: Subject Code '{$subjectCodeUpper}' should start with {$edpMajor} to match EDP Code '{$edpUpper}'.");
        return;
    }

    // 6. Section Validation - Check if section is sequential (ONLY for new subjects)
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

    // 7. Check for duplicate EDP code + Section (only when creating, not editing)
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

    // Re-validate department matches EDP code
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

    // 1. Full Validation
    $this->validate([
        'edp_code'       => 'required',
        'subject_code'   => 'required',
        'section'        => 'required|max:10',
        'description'    => 'required',
        'department'     => 'required',
        'units'          => 'required|integer|min:3|max:5',
        'type'           => 'required|in:Major,Minor',
        'duration_hours' => 'required|numeric|min:1|max:10',
    ]);

    // 2. Database Operation
    $subject = \App\Models\Subject::updateOrCreate(
        ['id' => $this->subjectId],
        [
            'edp_code'       => $edpUpper,
            'subject_code'   => $subjectCodeUpper,
            'section'        => strtoupper($this->section),
            'description'    => $this->description,
            'units'          => $this->units,
            'type'           => $this->type,
            'duration_hours' => $this->duration_hours,
            'department'     => $deptUpper,
        ]
    );

    // 3. Activity Logging & Notifications
    $this->logActivityAndNotify($subject, $user, $deptUpper);

    // 4. UI Reset
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

    // Notification Logic (Your existing recipient logic)
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

public function duplicateSubject($id)
{
    $subject = \App\Models\Subject::findOrFail($id);

    // Detect next section letter
    $currentSection = strtoupper($subject->section ?? 'A');
    
    if ($currentSection === 'Z') {
        $nextSection = 'AA';
    } elseif ($currentSection === 'AA') {
        $nextSection = 'AB';
    } else {
        $nextSection = chr(ord($currentSection) + 1);
    }

    // If the next section already exists, show warning
    $parts = explode('-', $subject->edp_code);
    $newEdp = $subject->edp_code;
    
    if (count($parts) >= 4) {
        array_pop($parts);
        $newEdp = implode('-', $parts) . '-' . $nextSection;
    }

    $exists = \App\Models\Subject::where('edp_code', $newEdp)->exists();

    if ($exists) {
        $this->dispatch('toast', [
            'type' => 'warning',
            'message' => 'Duplicate Aborted',
            'detail' => "Section {$nextSection} already exists ({$newEdp})."
        ]);
        return;
    }

    // Continue normal flow
    $this->duplicateCandidateId = $id;
    $this->showDuplicateConfirmModal = true;
    $this->showModal = false;
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
    $this->type = $original->type;
    $this->duration_hours = $original->duration_hours;
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

        // 1. Calculate next section
        $currentSection = strtoupper($original->section ?: 'A');
        
        // Handle Z -> AA transition
        if ($currentSection === 'Z') {
            $nextSection = 'AA';
        } elseif ($currentSection === 'AA') {
            $nextSection = 'AB';
        } else {
            $nextSection = chr(ord($currentSection) + 1);
        }

        // 2. Rebuild EDP Code (e.g., CCS-ACT-101-A -> CCS-ACT-101-B)
        $parts = explode('-', $original->edp_code);
        $newEdp = $original->edp_code; // Fallback
        
        if (count($parts) >= 4) {
            // Replace only the last part (section)
            array_pop($parts);
            $newEdp = implode('-', $parts) . '-' . $nextSection;
        } else {
            $newEdp = $original->edp_code . '-' . $nextSection;
        }

        // 3. CHECK IF NEW EDP CODE ALREADY EXISTS
        $exists = \App\Models\Subject::where('edp_code', $newEdp)->exists();
        
        if ($exists) {
            $skippedCount++;
            continue; // Skip this subject
        }

        // 4. Create the new record
        \App\Models\Subject::create([
            'edp_code'       => $newEdp,
            'subject_code'   => $original->subject_code,
            'section'        => $nextSection,
            'description'    => $original->description,
            'units'          => $original->units,
            'department'     => $original->department,
            'type'           => $original->type,
            'duration_hours' => $original->duration_hours,
        ]);

        $duplicatedCount++;
    }

    // 5. Activity Logging
    \App\Models\Activity::create([
        'user_id'     => $actor->id,
        'action'      => 'Bulk Duplicate',
        'module'      => 'Subjects',
        'description' => "Created {$duplicatedCount} new subject sections via bulk duplication." . ($skippedCount > 0 ? " ({$skippedCount} skipped - already exist)" : ""),
    ]);

    // 6. UI Feedback
    $this->reset(['selectedSubjects', 'selectAll']);
    $this->dispatch('subjectUpdated');
    
    // Show appropriate toast message
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
        // Keep everything before section (Dept-Major-Number)
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

        // Role-Based Access
        $query->where(function($q) use ($user, $isPowerUser) {
            if (!$isPowerUser) {
                $q->where('department', $user->department);
            } elseif (!empty($this->selectedDept)) {
                $q->where('department', $this->selectedDept);
            }
        });

        // Section Filter
        if (!empty($this->selectedSection)) {
            $query->where('section', $this->selectedSection);
        }

        // Search Filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('subject_code', 'like', "%{$this->search}%")
                  ->orWhere('edp_code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Year Filter - Extract from EDP code third segment
        if (!empty($this->selectedYear)) {
            $yearNumber = $this->selectedYear;
            $query->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(edp_code, '-', 3), '-', -1) LIKE ?", ["{$yearNumber}%"]);
        }

        // Major Filter
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
        // Identify the department of the subjects being deleted for scoped notification
        // We grab the department from the first selected subject as the target
        $sampleSubject = \App\Models\Subject::whereIn('id', $this->selectedSubjects)->first();
        $targetDept = $sampleSubject ? $sampleSubject->department : ($user->department ?? 'General');

        // 1. Database Operation
        \App\Models\Subject::whereIn('id', $this->selectedSubjects)->delete();

        // 2. Sidebar Activity Log
        \App\Models\Activity::create([
            'user_id'     => $user->id,
            'action'      => 'Delete',
            'module'      => 'Subjects',
            'description' => "Bulk removed {$count} subjects from the {$targetDept} catalog.",
        ]);

        // 3. SMART SCOPED NOTIFICATION (Hierarchical Flow)
        $recipients = \App\Models\User::where('id', '!=', $user->id)
            ->where(function($query) use ($targetDept) {
                $query->whereIn('role', ['admin', 'registrar', 'associate_dean']) // Global oversight
                      ->orWhere(function($q) use ($targetDept) {
                          $q->whereIn('role', ['dean', 'oic']) // Department Head
                            ->where('department', $targetDept); // Only notify the affected dept head
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

        // 4. UI FEEDBACK: Global Slide-in
        $this->dispatch('notify', [
            'type'        => 'error', 
            'title'       => 'REGISTRY PURGED', 
            'message'     => "Successfully removed {$count} subjects from the database.",
            'sender_name' => $user->name
        ]);

        // 5. UI FEEDBACK: Local Toast
        $this->dispatch('toast', [
            'type'    => 'success', 
            'message' => 'Batch Deleted', 
            'detail'  => "{$count} subjects successfully removed."
        ]);

        // 6. REFRESH: Sync for everyone
        $this->dispatch('subjectUpdated');

        // 7. Cleanup
        $this->reset(['selectedSubjects', 'selectAll']);
    }
}

/**
 * DELETE SINGLE SUBJECT
 */
public function deleteSubject($id)
{
    $user = auth()->user();
    $subject = \App\Models\Subject::findOrFail($id);
    $subjectCode = $subject->subject_code;
    $subjectDesc = $subject->description;
    $targetDept = $subject->department;

    // 1. SMART SCOPED NOTIFICATION (Hierarchical Flow)
    // We send this BEFORE deletion so the notification can still access subject data if needed
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

    // 2. Database Operation
    $subject->delete();

    // 3. Activity Log
    \App\Models\Activity::create([
        'user_id'     => $user->id,
        'action'      => 'Delete',
        'module'      => 'Subjects',
        'description' => "Manually removed subject {$subjectCode} from the {$targetDept} catalog.",
    ]);

    // 4. UI FEEDBACK: Global Slide-in
    $this->dispatch('notify', [
        'type'        => 'error', 
        'title'       => 'SUBJECT REMOVED', 
        'message'     => "{$subjectCode} has been deleted from the registry.",
        'sender_name' => $user->name
    ]);

    // 5. UI FEEDBACK: Local Toast
    $this->dispatch('toast', [
        'type'    => 'warning', 
        'message' => 'Subject Deleted', 
        'detail'  => "{$subjectCode} - {$subjectDesc} removed."
    ]);

    // 6. REFRESH: Sync Bell Icon and UI
    $this->dispatch('subjectUpdated');
}

    public function mount() {
    if (!in_array(strtolower(auth()->user()->role), ['admin', 'registrar', 'associate_dean'])) {
        $this->selectedDept = auth()->user()->department;
    } else {
        $this->selectedDept = ''; // Admins start with "All"
    }
}

private function broadcastSubjectChange($subjectData, $actionType, $targetDept)
{
    $actor = auth()->user();

    // 1. Identify recipients based on the 'Hybrid' flow
    $recipients = \App\Models\User::where('id', '!=', $actor->id) // Rule: Skip the person who did the action
        ->where(function($query) use ($targetDept) {
            // Tier 1: Global Authorities (Always get notified)
            $query->whereIn('role', ['admin', 'registrar', 'associate_dean']) 
                  // Tier 2: Departmental Heads (Dean or OIC)
                  ->orWhere(function($q) use ($targetDept) {
                      $q->whereIn('role', ['dean', 'oic'])
                        ->where('department', $targetDept); // Must match the subject's dept
                  });
        })->get();

    // 2. Dispatch to the Notifications table
    if ($recipients->count() > 0) {
        \Illuminate\Support\Facades\Notification::send(
            $recipients, 
            new \App\Notifications\SubjectUpdatedNotification($subjectData, $actionType)
        );
    }
}

public function render()
{
    $user = auth()->user();
    
    $userRole = strtolower($user->role ?? '');
    $powerRoles = ['admin', 'registrar', 'associate_dean'];
    $isPowerUser = in_array($userRole, $powerRoles);

    // Build the base query
    $query = \App\Models\Subject::query();

    // Role-Based Access Scoping
    $query->where(function($q) use ($user, $isPowerUser) {
        if (!$isPowerUser) {
            $q->where('department', $user->department);
        } else {
            if (!empty($this->selectedDept)) {
                $q->where('department', $this->selectedDept);
            }
        }
    });

    // Section Filter
    if (!empty($this->selectedSection)) {
        $query->where('section', $this->selectedSection);
    }

    // Search Logic
    if (!empty($this->search)) {
        $query->where(function($q) {
            $q->where('subject_code', 'like', "%{$this->search}%")
              ->orWhere('edp_code', 'like', "%{$this->search}%")
              ->orWhere('description', 'like', "%{$this->search}%");
        });
    }

    // Year Filter - Extract year from EDP code (e.g., CCS-ACT-101-A -> 1st year, CCS-ACT-201-A -> 2nd year)
    if (!empty($this->selectedYear)) {
        $yearNumber = $this->selectedYear; // "1", "2", "3", or "4"
        $query->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(edp_code, '-', 3), '-', -1) LIKE ?", ["{$yearNumber}%"]);
    }

    // Major Filter - Match subject_code or the major part in EDP code
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