<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SubjectUpdatedNotification;
use App\Models\Subject;

class ManageSubjects extends Component
{
    use WithFileUploads, WithPagination;

    // UI States
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;
    public $search = '';
    public $selectedDept = '';
    public $selectedYear = '';
    public $selectedMajor = '';

    // Form Fields
    public $subjectId, $edp_code, $subject_code, $description, $department, $units;
    public $type = 'Major';
    public $duration_hours = 3;

    // CSV Import Logic
    public $importFile;
    public $previewData = [];
    public $selectedSubjects = [];
    public $selectAll = false;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedDept() { $this->resetPage(); }

    
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
    $expected = ['edp_code', 'subject_code', 'description', 'units', 'department', 'duration_hours', 'type'];
    
    if ($normalizedHeader !== $expected) {
        $this->abortImport('Invalid Subject Template');
        return;
    }

    // 3. SUCCESS: Preview the data
    $this->previewData = array_slice($data, 0, 10);
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

        $rowDept = strtoupper(trim($row[4] ?? ''));
        
        if (!$isPowerUser && $rowDept !== strtoupper($actor->department)) {
            continue; 
        }

        if (!$detectedDept && !empty($rowDept)) {
            $detectedDept = $rowDept;
        }

        // Mapping duration and type from CSV
        $col5 = trim($row[5] ?? '');
        $col6 = trim($row[6] ?? '');
        $isCol5Duration = str_contains(strtolower($col5), 'hrs');
        $rawDuration = $isCol5Duration ? $col5 : $col6;
        $rawType = $isCol5Duration ? $col6 : $col5;

        \App\Models\Subject::updateOrCreate(
            ['edp_code' => strtoupper(trim($row[0]))],
            [
                'subject_code'   => strtoupper(trim($row[1])),
                'description'    => trim($row[2]),
                'units'          => (int)($row[3]),
                'department'     => $rowDept,
                'duration_hours' => (int) filter_var($rawDuration, FILTER_SANITIZE_NUMBER_INT) ?: 3,
                'type'           => !empty($rawType) ? ucfirst(strtolower($rawType)) : 'Major',
            ]
        );
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
            'description' => "Successfully imported {$count} subjects into the {$targetDept} department.",
        ]);

        // 2. BROADCAST NOTIFICATION: Send to other admins, exclude the current user
        $recipients = \App\Models\User::whereIn('role', ['admin', 'registrar', 'associate_dean'])
            ->where('id', '!=', $actor->id)
            ->get();

        \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\SubjectUpdatedNotification(
            (object)[
                'subject_code' => 'BATCH IMPORT', 
                'subject_description' => "{$count} Subjects added to {$targetDept}"
            ], 
            'subject_imported' // Corrected type name
        ));

        // 3. UI FEEDBACK: Popup notification (Top-Right)
        $this->dispatch('notify', [
            'type' => 'success', // Use 'success' for blue/green styling instead of room_import
            'title' => 'CATALOG SYNCED',
            'message' => "successfully batch-imported {$count} subjects.",
            'sender_name' => $actor->name
        ]);

        // 4. REFRESH: Tell the Bell Icon to update for everyone else
        $this->dispatch('subjectUpdated');

        // 5. Toast Feedback
        $this->dispatch('toast', [
            'type' => 'success', 
            'message' => 'Import Complete', 
            'detail' => "{$count} subjects added/updated for {$targetDept}."
        ]);
    } else {
        $this->dispatch('toast', [
            'type' => 'error', 
            'message' => 'Import Failed', 
            'detail' => "No valid subjects found."
        ]);
    }

    $this->reset(['importFile', 'bulkOpen', 'previewData']);
}
    public function openModal()
{
    $this->resetValidation();
    $this->resetExcept(['selectedDept', 'search', 'selectedYear', 'selectedMajor']);
    $this->isEditMode = false;

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
        $this->description = $subject->description;
        $this->units = $subject->units;
        $this->type = $subject->type ?? 'Major';
        $this->duration_hours = $subject->duration_hours ?? 3;
        $this->department = $subject->department;
        
        $this->showModal = true;
    }

    public function saveSubject()
{
    $user = auth()->user();
    $userRole = strtolower($user->role ?? '');
    $powerRoles = ['admin', 'registrar', 'associate_dean'];
    $isPowerUser = in_array($userRole, $powerRoles);

    // 1. Logic & Security: Force department for non-power users
    if (!$isPowerUser) {
        $this->department = $user->department;
    }

    $edpUpper = strtoupper($this->edp_code);
    $deptUpper = strtoupper($this->department);

    // EDP Code Validation (Specific to your institution's rule)
    if (!str_starts_with($edpUpper, $deptUpper)) {
        $this->addError('department', "Mismatch: EDP Code '{$edpUpper}' does not belong to {$deptUpper}.");
        return; 
    }

    // 2. Validation
    $this->validate([
        'edp_code'       => 'required|unique:subjects,edp_code,' . $this->subjectId,
        'subject_code'   => 'required',
        'description'    => 'required',
        'department'     => 'required',
        'units'          => 'required|numeric',
        'type'           => 'required|in:Major,Minor',
        'duration_hours' => 'required|numeric|min:1|max:10',
    ]);

    // 3. Database Operation
    $subject = \App\Models\Subject::updateOrCreate(
        ['id' => $this->subjectId],
        [
            'edp_code'       => $edpUpper,
            'subject_code'   => strtoupper($this->subject_code),
            'description'    => $this->description,
            'units'          => $this->units,
            'type'           => $this->type,
            'duration_hours' => $this->duration_hours,
            'department'     => $deptUpper,
        ]
    );

    // 4. BROADCAST NOTIFICATION: Send to OTHERS only
    // This finds all power users EXCEPT the person currently saving
    $recipients = \App\Models\User::whereIn('role', ['admin', 'registrar', 'associate_dean'])
        ->where('id', '!=', $user->id)
        ->get();

    if ($recipients->count() > 0) {
        \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\SubjectUpdatedNotification(
            $subject, 
            $this->isEditMode ? 'updated' : 'created'
        ));
    }

    // 5. DETAILED ACTIVITY LOGGING (Sidebar)
    \App\Models\Activity::create([
        'user_id'     => $user->id,
        'action'      => $this->isEditMode ? 'Update' : 'Add',
        'module'      => 'Subjects',
        'description' => $this->isEditMode 
            ? "Updated subject {$subject->subject_code} in the {$this->department} department."
            : "Manually added {$subject->subject_code} to the {$this->department} catalog.",
    ]);

    // 6. UI FEEDBACK (Toast & Real-time Refresh)
    $this->showModal = false;

    // Trigger the Toast (immediate popup for the person doing the work)
    $this->dispatch('toast', [
        'type'    => 'success', 
        'message' => $this->isEditMode ? 'Subject Updated' : 'Subject Created', 
        'detail'  => "{$subject->subject_code} is now active in the {$this->department} department."
    ]);

    // Trigger the Global Notification (the top-right slide-in)
    $this->dispatch('notify', [
        'type'    => 'success', 
        'title'   => $this->isEditMode ? 'CATALOG UPDATED' : 'NEW SUBJECT ADDED', 
        'message' => "{$subject->subject_code} has been synchronized successfully.",
        'sender_name' => $user->name
    ]);

    // This triggers the #[On('subjectUpdated')] in NotificationCenter.php for everyone
    $this->dispatch('subjectUpdated');

    // 7. Cleanup
    $this->reset(['edp_code', 'subject_code', 'description', 'units', 'type', 'duration_hours', 'department', 'subjectId']);
}

public function updatedSelectAll($value)
    {
        if ($value) {
            // Get all IDs from the CURRENT filtered/searched list
            $this->selectedSubjects = Subject::query()
                ->where(function($query) {
                    if (!in_array(strtolower(auth()->user()->role), ['admin', 'registrar', 'associate_dean'])) {
                        $query->where('department', auth()->user()->department);
                    }
                })
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
        // 1. Database Operation
        \App\Models\Subject::whereIn('id', $this->selectedSubjects)->delete();

        // 2. Sidebar Activity Log
        \App\Models\Activity::create([
            'user_id' => $user->id,
            'action' => 'Delete',
            'module' => 'Subjects',
            'description' => "Bulk removed {$count} subjects from the central catalog.",
        ]);

        // 3. BROADCAST NOTIFICATION: Send to OTHERS only
        $recipients = \App\Models\User::whereIn('role', ['admin', 'registrar', 'associate_dean'])
            ->where('id', '!=', $user->id)
            ->get();

        if ($recipients->count() > 0) {
            \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\SubjectUpdatedNotification(
                (object)[
                    'subject_code' => 'BATCH PURGE', 
                    'subject_description' => "Multiple Subject Records"
                ], 
                'deleted' 
            ));
        }

        // 4. UI FEEDBACK: Global Notification (Top Right - immediate feedback for YOU)
        $this->dispatch('notify', [
            'type' => 'error', 
            'title' => 'REGISTRY PURGED', 
            'message' => "Successfully removed {$count} subjects from the database.",
            'sender_name' => $user->name
        ]);

        // 5. UI FEEDBACK: Toast (Bottom Right)
        $this->dispatch('toast', [
            'type' => 'success', 
            'message' => 'Batch Deleted', 
            'detail' => "{$count} subjects successfully removed."
        ]);

        // 6. REFRESH: Sync UI for everyone
        $this->dispatch('subjectUpdated');

        // 7. Cleanup
        $this->reset(['selectedSubjects', 'selectAll']);
    }
}

public function deleteSubject($id)
{
    $user = auth()->user();
    $subject = \App\Models\Subject::findOrFail($id);
    $subjectCode = $subject->subject_code;
    $subjectDesc = $subject->description;

    // 1. BROADCAST NOTIFICATION: Send to OTHERS only
    $recipients = \App\Models\User::whereIn('role', ['admin', 'registrar', 'associate_dean'])
        ->where('id', '!=', $user->id)
        ->get();

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
        'user_id' => $user->id,
        'action' => 'Delete',
        'module' => 'Subjects',
        'description' => "Manually removed subject {$subjectCode} from the catalog.",
    ]);

    // 4. UI FEEDBACK: Global Notification
    $this->dispatch('notify', [
        'type' => 'error', 
        'title' => 'SUBJECT REMOVED', 
        'message' => "{$subjectCode} has been deleted from the registry.",
        'sender_name' => $user->name
    ]);

    // 5. UI FEEDBACK: Toast
    $this->dispatch('toast', [
        'type' => 'warning', 
        'message' => 'Subject Deleted', 
        'detail' => "{$subjectCode} - {$subjectDesc} removed."
    ]);

    // 6. REFRESH: Bell Icon for teammates
    $this->dispatch('subjectUpdated');
}

    public function mount() {
    if (!in_array(strtolower(auth()->user()->role), ['admin', 'registrar', 'associate_dean'])) {
        $this->selectedDept = auth()->user()->department;
    } else {
        $this->selectedDept = ''; // Admins start with "All"
    }
}
public function render()
{
    $user = auth()->user();
    
    // Normalize role from database (matches your 'associate_dean' screenshot)
    $userRole = strtolower($user->role);
    
    // Define power roles
    $powerRoles = ['admin', 'registrar', 'associate_dean'];
    $isPowerUser = in_array($userRole, $powerRoles);

    return view('livewire.manage-subjects', [
        // 1. SUBJECTS QUERY
        'subjects' => \App\Models\Subject::query()
            // Role-Based Access Scoping
            ->where(function($query) use ($user, $isPowerUser) {
                if (!$isPowerUser) {
                    // Regular Dean/OIC: Locked to their own department
                    $query->where('department', $user->department);
                } else {
                    // Power Users: Filter by dropdown if selected, otherwise show all
                    if (!empty($this->selectedDept)) {
                        $query->where('department', $this->selectedDept);
                    }
                }
            })

            // Search Logic
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('subject_code', 'like', "%{$this->search}%")
                      ->orWhere('edp_code', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            })

            // Year and Major Filters
            ->when($this->selectedYear, function($query) {
                $query->where('edp_code', 'like', '%-%-' . $this->selectedYear . '%');
            })
            ->when($this->selectedMajor, function($query) {
                $query->where('subject_code', 'like', $this->selectedMajor . '%');
            })
            
            ->orderBy('edp_code', 'asc')
            ->paginate(10), // <--- This comma was the missing piece!

        // 2. ACTIVITIES QUERY (Recent Activity Feed)
        'activities' => \App\Models\Activity::with('user')
            ->latest()
            ->take(10)
            ->get()
    ]);
}
}