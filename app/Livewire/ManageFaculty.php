<?php

namespace App\Livewire;

use App\Models\Faculty;
use App\Models\FacultyLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FacultyRequestNotification;

class ManageFaculty extends Component
{
    use WithPagination, WithFileUploads;

    // Filters & UI State
    public $search = '';
    public $filterDepartment = ''; 
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;
    public $importFile;
    public $importPreview = [];
    public $bulk = false;
    

    // Form fields
    public $faculty_id; 
    public $employee_id, $full_name, $email, $department;
    public $selectedFaculty = [];
    public $selectAll = false;
    public $confirmingDeletion = false;
    public $importSuccess = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => ''],
    ];
    private function isGlobalViewer()
    {
        return in_array(auth()->user()->role, ['admin', 'registrar', 'associate_dean']);
    }
    private function isAdminOrRegistrar()
    {
        return in_array(auth()->user()->role, ['admin', 'registrar']);
    }

   public function updatedSelectAll($value)
{
    if ($value) {
        $user = auth()->user();
        
        $this->selectedFaculty = Faculty::query()
            // Admins and Registrars get everything (Approved + Rejected)
            ->whereIn('status', ['approved', 'rejected'])
            
            // Apply restrictions for Deans, OICs, and Associate Deans
            ->when(!$this->isAdminOrRegistrar(), function($q) use ($user) {
                
                // 1. Associate Dean: Can select ANY rejected faculty (all depts)
                if ($user->role === 'associate_dean') {
                    return $q->where('status', 'rejected');
                }

                // 2. Department Deans/OICs: Only their own department's rejected records
                return $q->where('department', $user->department)
                         ->where('status', 'rejected');
            })
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();
       
        // UI Feedback: If they tried to select all but nothing is deletable for their role
        if(!$this->isAdminOrRegistrar() && empty($this->selectedFaculty)) {
             $this->dispatch('swal', [
                 'title' => 'No Records Selected', 
                 'text' => 'There are no "Rejected" records available for you to delete.', 
                 'icon' => 'info'
             ]);
             $this->selectAll = false;
        }
    } else {
        // Uncheck everything
        $this->selectedFaculty = [];
    }
}

    private function logAction($facultyId, $action, $description, $department = null) 
{
    FacultyLog::create([
        'faculty_id'  => $facultyId, 
        'user_id'     => auth()->id(), 
        'action'      => $action,      
        'description' => $description,
        'department'  => $department ?? (auth()->user()->role === 'dean' ? auth()->user()->department : null),
    ]);
}

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterDepartment() { $this->resetPage(); }

public function openModal() 
{
    $this->reset(['employee_id', 'full_name', 'email', 'department', 'faculty_id']);
    $this->resetValidation();
    $this->isEditMode = false;

    $lastFaculty = \App\Models\Faculty::orderBy('id', 'desc')->first();
    $lastNum = $lastFaculty ? (int)filter_var($lastFaculty->employee_id, FILTER_SANITIZE_NUMBER_INT) : 0;
    $this->employee_id = "EMP" . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);

    // 4. Auto-lock Department if the user is a Dean
    if (auth()->user()->department) {
        $this->department = auth()->user()->department;
    }

    $this->showModal = true; 
}

    protected $rules = [
        'employee_id' => 'required|unique:faculties,employee_id',
        'full_name'   => 'required|min:5|regex:/(\s)/',
        'email'       => 'required|email|contains:@',
        'department'  => 'required',
    ];

   public function saveFaculty() 
{
    // 1. Validation
    $this->validate([
        'employee_id' => 'required|unique:faculties,employee_id',
        'full_name'   => 'required|unique:faculties,full_name|min:5|regex:/(\s)/', 
        'email'       => 'required|unique:faculties,email|email', 
        'department'  => 'required',
    ], [
        'full_name.unique'   => '⚠️ This name is already being used.',
        'email.unique'       => '⚠️ This email is already being used.',
        'employee_id.unique' => '⚠️ This ID is already assigned.',
        'full_name.regex'    => '⚠️ Please enter your complete full name.',
        'email.email'        => "⚠️ Please enter a valid email containing '@'.",
        'full_name.min'      => '⚠️ Name is too short.',
        'email.required'     => '⚠️ The email address is required.',
    ]);

    // 2. Define Status (Admins auto-approve, Deans/OICs stay pending)
    $status = $this->isAdminOrRegistrar() ? 'approved' : 'pending';

    // 3. Create the Record
    $newFaculty = Faculty::create([
        'employee_id'  => $this->employee_id,
        'full_name'    => $this->full_name,
        'email'        => $this->email,
        'department'   => $this->department,
        'status'       => $status,
        'requested_by' => auth()->id(),
    ]);

    // 4. Notify Relevant Roles (Only if it's a request from a Dean/OIC)
    // Find this part in saveFaculty() and replace the notification loop:
if (!$this->isAdminOrRegistrar()) {
    $allRecipients = $this->getStakeholders($newFaculty->department);
    foreach ($allRecipients as $recipient) {
        if ($recipient->id === auth()->id()) continue;
        $recipient->notify(new FacultyRequestNotification($newFaculty, auth()->user()->name, 'pending'));
    }
}

    // 5. Audit Trail & UI Updates
    $this->logAction(
        $newFaculty->id, 
        'created', 
        strtoupper(auth()->user()->role) . " added faculty: {$newFaculty->full_name}",
        $newFaculty->department
    );

    $this->showModal = false;

    $this->dispatch('toast', [
        'type' => 'success', 
        'message' => 'Faculty Registered', 
        'detail' => "{$this->full_name} has been added to the registry as " . strtoupper($status) . "."
    ]);

    // Refresh components
    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
}

   public function deleteSelected()
{
    if (empty($this->selectedFaculty)) return;

    $actor = auth()->user();
    $faculties = Faculty::whereIn('id', $this->selectedFaculty)->get();
    $count = 0;

    foreach ($faculties as $faculty) {
        // --- PERMISSION GATE ---
        $isAdminOrRegistrar = in_array($actor->role, ['admin', 'registrar']);
        $isAssociateDean = ($actor->role === 'associate_dean');
        $isDeptHead = in_array($actor->role, ['dean', 'oic']) && ($faculty->department === $actor->department);
        $isRejected = ($faculty->status === 'rejected');

        // Check if current actor can delete this specific record
        if (!$isAdminOrRegistrar && !(($isAssociateDean || $isDeptHead) && $isRejected)) {
            continue;
        }

        $name = $faculty->full_name;
        $dept = $faculty->department;

        // 1. Notify Stakeholders for this specific deletion
        $recipients = User::query()
            ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
            ->orWhere(function($q) use ($dept) {
                $q->where('department', $dept)
                  ->whereIn('role', ['dean', 'oic']);
            })
            ->get();

        foreach ($recipients->unique('id') as $recipient) {
            if ($recipient->id === $actor->id) continue;

            $recipient->notify(new \App\Notifications\FacultyRequestNotification(
                $name, 
                $actor->name, 
                'deleted'
            ));
        }

        // 2. Cleanup Logs & Audit Trail
        \App\Models\FacultyLog::where('faculty_id', $faculty->id)->delete();
        
        $this->logAction(
            null, 
            'deleted', 
            strtoupper($actor->role) . " ({$actor->name}) bulk-deleted faculty: {$name}",
            $dept
        );

        $faculty->delete();
        $count++;
    }

    // Reset UI
    $this->reset(['selectedFaculty', 'selectAll', 'confirmingDeletion']);
    
    if ($count > 0) {
        $this->dispatch('toast', [
            'type'    => 'warning', 
            'message' => 'Bulk Deletion Complete', 
            'detail'  => "$count records were removed. Relevant Department Heads notified."
        ]);
    } else {
        $this->dispatch('toast', [
            'type'    => 'info', 
            'message' => 'No Records Deleted', 
            'detail'  => "No records were removed due to permission restrictions."
        ]);
    }

    $this->dispatch('facultyUpdated')->to(\App\Livewire\NotificationCenter::class);
} 
    public function editFaculty($id) 
{
    $this->resetValidation();
    $f = Faculty::findOrFail($id);
    
    $this->faculty_id  = $f->id;
    $this->employee_id = $f->employee_id;
    $this->full_name   = $f->full_name;
    $this->email       = $f->email; // <-- Check if this is being set
    $this->department  = $f->department;
    
    $this->isEditMode = true;
    $this->showModal  = true;
}

    public function updateFaculty() 
{
    // 1. Safety Check
    if (!$this->faculty_id) {
        $this->dispatch('toast', ['type' => 'error', 'message' => 'Error', 'detail' => 'Record ID not found.']);
        return;
    }

    // 2. Validation with Unique Ignore
    $this->validate([
        'employee_id' => [
            'required', 
            Rule::unique('faculties', 'employee_id')->ignore($this->faculty_id)
        ],
        'full_name' => [
            'required', 
            'min:5', 
            'regex:/(\s)/', 
            Rule::unique('faculties', 'full_name')->ignore($this->faculty_id)
        ],
        'email' => [
            'required', 
            'email', 
            Rule::unique('faculties', 'email')->ignore($this->faculty_id)
        ],
        'department' => 'required',
    ], [
        'full_name.unique'   => '⚠️ That name is already being used by another record.',
        'email.unique'       => '⚠️ That email address is already being used by another record.',
        'employee_id.unique' => '⚠️ That Employee ID is already assigned.',
        'full_name.regex'    => '⚠️ Please enter the complete full name (First and Last).',
        'email.required'     => '⚠️ The email address is required.',
    ]);

    // 3. Find and Update
    $faculty = Faculty::findOrFail($this->faculty_id);
    $actor = auth()->user();
    
    $faculty->update([
        'employee_id' => $this->employee_id,
        'full_name'   => $this->full_name,
        'email'       => $this->email,
        'department'  => $this->department,
    ]);

    // 4. Log Action
    $this->logAction(
        $faculty->id, 
        'updated', 
        strtoupper($actor->role) . " updated details for: {$faculty->full_name}",
        $faculty->department
    );

    // 5. Smart Notifications (Inclusive of OIC)
    // We notify stakeholders if an Admin/Registrar makes changes to a department record
    if ($this->isAdminOrRegistrar()) {
        
        // Find everyone who needs to know:
        // - Associate Dean (Global)
        // - Dean/OIC of this specific department
        $recipients = User::query()
            ->where('role', 'associate_dean')
            ->orWhere(function($query) use ($faculty) {
                $query->where('department', $faculty->department)
                      ->whereIn('role', ['dean', 'oic']);
            })
            ->get();

        foreach ($recipients->unique('id') as $recipient) {
            // Don't notify the person who actually made the edit
            if ($recipient->id === $actor->id) continue;

            $recipient->notify(new FacultyRequestNotification(
                $faculty, 
                $actor->name, 
                'edited'
            ));
        }
    }

    // 6. UI Feedback & State Reset
    $this->showModal = false;
    
    $this->dispatch('toast', [
        'type'    => 'success', 
        'message' => 'Record Updated', 
        'detail'  => "Details for {$this->full_name} have been updated successfully."
    ]);

    // Refresh the notification bell and tables
    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
}
    public function deleteFaculty($id)
{
    $faculty = Faculty::findOrFail($id);
    $actor = auth()->user();
    
    // --- PERMISSION GATE ---
    $isAdminOrRegistrar = in_array($actor->role, ['admin', 'registrar']);
    $isAssociateDean = ($actor->role === 'associate_dean');
    // Check if user is the Dean or OIC of the faculty's department
    $isDeptHead = in_array($actor->role, ['dean', 'oic']) && ($faculty->department === $actor->department);
    $isRejected = ($faculty->status === 'rejected');

    // Admin/Registrar = Total Access. Others = Only if record is 'rejected'.
    if (!$isAdminOrRegistrar && !(($isAssociateDean || $isDeptHead) && $isRejected)) {
        $this->dispatch('toast', [
            'type' => 'error', 
            'message' => 'Access Denied', 
            'detail' => 'You do not have permission to delete active records.'
        ]);
        return;
    }

    // 1. Capture details for logs and notifications
    $name = $faculty->full_name;
    $dept = $faculty->department;
    $status = strtoupper($faculty->status);
    $actorRole = strtoupper($actor->role); 

    // 2. Database Cleanup
    \App\Models\FacultyLog::where('faculty_id', $id)->delete();

    // 3. System Log
    $this->logAction(
        null, 
        'deleted', 
        "{$actorRole} ({$actor->name}) removed the {$status} faculty: {$name}",
        $dept
    );

    // 4. Inclusive Notification Flow
    $recipients = User::query()
        // Global Leaders
        ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
        // Specific Department Leaders (Dean & OIC)
        ->orWhere(function($q) use ($dept) {
            $q->where('department', $dept)
              ->whereIn('role', ['dean', 'oic']);
        })
        ->get();

    foreach ($recipients->unique('id') as $recipient) {
        if ($recipient->id === $actor->id) continue; // Skip the actor

        $recipient->notify(new \App\Notifications\FacultyRequestNotification(
            $name, 
            $actor->name, 
            'deleted'
        ));
    }

    // 5. Final Delete
    $faculty->delete();

    $this->dispatch('toast', [
        'type' => 'warning', 
        'message' => 'Faculty Removed', 
        'detail' => "{$name} has been removed from the registry."
    ]);

    $this->dispatch('facultyUpdated')->to(\App\Livewire\NotificationCenter::class);
}

    public function updatedImportFile()
{
    $this->validate([
        'importFile' => 'required|mimes:csv,txt|max:10240',
    ]);

    $path = $this->importFile->getRealPath();
    $data = array_map('str_getcsv', file($path));
    $headers = array_map('trim', $data[0]);

    // Check for Subject or Room files specifically
    if (in_array('subject_code', $headers) || in_array('room_name', $headers)) {
        $type = in_array('subject_code', $headers) ? 'SUBJECT' : 'ROOM';
        $this->dispatch('toast', [
    'type'    => 'error', 
    'message' => 'Wrong File Type', 
    'detail'  => "🚨 This is a $type file. Please upload a Faculty CSV."
]);
        $this->reset(['importFile', 'importPreview']);
        return;
    }

    $required = ['employee_id', 'full_name'];
    foreach($required as $key) {
        if (!in_array($key, $headers)) {
            $this->dispatch('toast', [
                    'type'    => 'error', 
                    'message' => 'Invalid Format', 
                    'detail'  => "The file is missing the '$key' column."
                ]);
            $this->reset(['importFile', 'importPreview']);
            return;
        }
    }

    // If passed, proceed to preview...
    $this->importPreview = [];
    foreach (array_slice($data, 1) as $row) {
        if (count($row) < 2) continue;
        $this->importPreview[] = [
            'employee_id' => $row[0],
            'full_name'   => $row[1],
            'email'       => $row[2] ?? '',
            'department'  => $row[3] ?? '',
            'error'       => Faculty::where('employee_id', $row[0])->exists(),
        ];
    }
    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
    }

   public function processImport()
{
    try {
        $importCount = 0;
        $importedDepartments = [];

        \Illuminate\Support\Facades\DB::transaction(function () use (&$importCount, &$importedDepartments) {
            foreach ($this->importPreview as $data) {
                // Duplicate Protection
                $exists = Faculty::where('employee_id', $data['employee_id'])
                    ->orWhere('email', $data['email'])
                    ->exists();

                if ($exists) continue;

                $faculty = Faculty::create([
                    'employee_id'  => $data['employee_id'],
                    'full_name'    => $data['full_name'],
                    'email'        => $data['email'],
                    'department'   => $data['department'],
                    'status'       => 'approved', 
                    'requested_by' => auth()->id(),
                ]);

                $this->logAction(
                    $faculty->id, 
                    'created', 
                    strtoupper(auth()->user()->role) . " imported faculty: {$faculty->full_name}",
                    $faculty->department
                );

                if (!empty($data['department'])) {
                    $importedDepartments[] = $data['department'];
                }

                $importCount++;
            }
        });

        if ($importCount > 0) {
            $senderName = auth()->user()->name;
            $uniqueDepts = array_unique($importedDepartments);

            // A. NOTIFY DEPARTMENT-SPECIFIC LEADERS (Dean & OIC)
            foreach ($uniqueDepts as $deptName) {
                $deptLeaders = User::where('department', $deptName)
                    ->whereIn('role', ['dean', 'oic'])
                    ->get();

                foreach ($deptLeaders as $leader) {
                    if ($leader->id === auth()->id()) continue; // Don't notify the one who did the work

                    $leader->notify(new FacultyRequestNotification(
                        "the $deptName Department", 
                        $senderName, 
                        'bulk_added'
                    ));
                }
            }

            // B. NOTIFY GLOBAL LEADERS (Admin, Registrar, Assoc Dean)
            $globalLeaders = User::whereIn('role', ['admin', 'registrar', 'associate_dean'])->get();
            foreach ($globalLeaders as $global) {
                if ($global->id === auth()->id()) continue;

                $global->notify(new FacultyRequestNotification(
                    "$importCount Faculty Members", 
                    $senderName, 
                    'bulk_added'
                ));
            }

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        }

        $this->reset(['importFile', 'importPreview', 'bulkOpen']); 
        $this->dispatch('close-import-modal'); 

        $this->dispatch('toast', [
            'type'    => 'success', 
            'message' => 'Import Successful', 
            'detail'  => "Added $importCount records. Relevant Department Heads and Admins have been notified."
        ]);

    } catch (\Exception $e) {
        $this->dispatch('close-import-modal');
        $this->dispatch('toast', [
            'type'    => 'error', 
            'message' => 'Import Failed', 
            'detail'  => 'An error occurred during processing.'
        ]);
    }
}
public function approveFaculty($id) 
{
    if (!$this->isAdminOrRegistrar()) return;

    $faculty = Faculty::findOrFail($id);
    $actor = auth()->user();

    $faculty->update(['status' => 'approved']);
    
    $this->logAction(
        $faculty->id, 
        'approved', 
        strtoupper($actor->role) . " approved faculty: {$faculty->full_name}",
        $faculty->department
    );

    // 1. Get Recipients: Associate Dean + Dept Dean/OIC + Original Requester
    $recipients = User::query()
        ->where('role', 'associate_dean')
        ->orWhere(function($q) use ($faculty) {
            $q->where('department', $faculty->department)
              ->whereIn('role', ['dean', 'oic']);
        })
        ->orWhere('id', $faculty->requested_by)
        ->get();

    // 2. Loop and Notify
    foreach ($recipients->unique('id') as $recipient) {
        if ($recipient->id === $actor->id) continue; // Skip the person who clicked 'Approve'

        $recipient->notify(new FacultyRequestNotification(
            $faculty, 
            $actor->name, 
            'approved'
        ));
    }

    $this->dispatch('toast', [
        'type'    => 'success',
        'message' => 'Request Approved',
        'detail'  => "{$faculty->full_name} is now active. Requester and Department Heads notified."
    ]);

    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
}

/**
 * Decline/Reject a pending faculty registration request.
 */
public function declineFaculty($id) 
{
    if (!$this->isAdminOrRegistrar()) return;

    $faculty = Faculty::findOrFail($id);
    $actor = auth()->user();
    
    $faculty->update(['status' => 'rejected']);

    $this->logAction(
        $faculty->id, 
        'rejected', 
        strtoupper($actor->role) . " declined registration: {$faculty->full_name}",
        $faculty->department
    );
    
    // 1. Get Recipients: Associate Dean + Dept Dean/OIC + Original Requester
    $recipients = User::query()
        ->where('role', 'associate_dean')
        ->orWhere(function($q) use ($faculty) {
            $q->where('department', $faculty->department)
              ->whereIn('role', ['dean', 'oic']);
        })
        ->orWhere('id', $faculty->requested_by)
        ->get();

    foreach ($recipients->unique('id') as $recipient) {
        if ($recipient->id === $actor->id) continue; // Skip the person who clicked 'Decline'

        $recipient->notify(new FacultyRequestNotification(
            $faculty, 
            $actor->name, 
            'rejected'
        ));
    }
    
    $this->dispatch('toast', [
        'type'    => 'info',
        'message' => 'Request Declined',
        'detail'  => "Registration for {$faculty->full_name} rejected. Requester notified."
    ]);

    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
}
    // --- EXPORT ---
    public function exportCSV() {
        $user = auth()->user();
        $filename = "faculty_list_" . now()->format('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Employee ID', 'Full Name', 'Email', 'Department', 'Status'];

        $callback = function() use ($columns, $user) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $data = Faculty::whereIn('status', ['approved', 'rejected'])
                ->when(!$this->isAdminOrRegistrar(), function($query) use ($user) {
                    return $query->where('department', $user->department);
                })->get();
            foreach ($data as $row) {
                fputcsv($file, [$row->employee_id, $row->full_name, $row->email, $row->department, ucfirst($row->status)]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
    
    private function getStakeholders($department)
{
    return User::query()
        ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
        ->orWhere(function($q) use ($department) {
            $q->where('department', $department)
              ->whereIn('role', ['dean', 'oic']);
        })
        ->get()
        ->unique('id');
}

public function render()
{
    $user = auth()->user();

    // 1. AUTO-FILTER LOGIC
    // If NOT a global viewer, lock them to their own department
    // Associate Deans will bypass this because isGlobalViewer() returns true
    if (!$this->isGlobalViewer()) { 
        $this->filterDepartment = $user->department; 
    }

    // 2. PENDING REQUESTS
    $pendingRequests = Faculty::where('status', 'pending')
        ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
            // Deans see only their own requests. 
            // Registrar/Admin/Assoc Dean see ALL pending requests to approve them.
            return $q->where('requested_by', $user->id);
        })->orderBy('created_at', 'desc')->get();

    // 3. MAIN FACULTY TABLE
    $faculties = Faculty::query()
        ->whereIn('status', ['approved', 'rejected']) 
        ->when(!$this->isGlobalViewer(), function ($q) use ($user) {
            // Only lock to department if NOT a Global Viewer
            return $q->where('department', $user->department);
        })
        ->when($this->filterDepartment && $this->isGlobalViewer(), function ($q) {
            // Allow Global Viewers to use the dropdown to filter
            return $q->where('department', $this->filterDepartment);
        })
        ->when($this->search, function ($q) {
            $q->where(function ($sub) {
                $sub->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('employee_id', 'like', "%{$this->search}%");
            });
        })->orderBy('employee_id', 'asc')->paginate(10);

    // 4. RECENT ACTIVITY LOGS
    $recentLogs = FacultyLog::with(['user', 'faculty'])
        ->where(function ($q) use ($user) {
            if ($this->isGlobalViewer()) {
                // Global Viewers see everything
                $q->whereRaw('1=1'); 
            } else {
                // Local Deans only see their department logs
                $q->where('user_id', $user->id)
                  ->orWhereHas('faculty', function ($f) use ($user) {
                      $f->where('department', $user->department);
                  });
            }
        })
        ->latest()
        ->take(15)
        ->get();

    return view('livewire.manage-faculty', [
        'faculties' => $faculties,
        'pendingRequests' => $pendingRequests,
        'recentLogs' => $recentLogs
    ]);
}
}

