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
    $this->validate([
        'employee_id' => 'required|unique:faculties,employee_id',
        'full_name'   => 'required|unique:faculties,full_name|min:5|regex:/(\s)/', 
        'email'       => 'required|unique:faculties,email|email', 
        'department'  => 'required',
    ], [
        // Duplicate Indicators
        'full_name.unique'   => '⚠️ This name is already being used.',
        'email.unique'       => '⚠️ This email is already being used.',
        'employee_id.unique' => '⚠️ This ID is already assigned.',
        
        // Formatting Indicators
        'full_name.regex'    => '⚠️ Please enter your complete full name.',
        'email.email'        => "⚠️ Please enter a valid email containing '@'.",
        'full_name.min'      => '⚠️ Name is too short.',
        'email.required'     => '⚠️ The email address is required.',
    ]);

    $status = $this->isAdminOrRegistrar() ? 'approved' : 'pending';
    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);

    $newFaculty = Faculty::create([
        'employee_id'  => $this->employee_id,
        'full_name'    => $this->full_name,
        'email'        => $this->email,
        'department'   => $this->department,
        'status'       => $status,
        'requested_by' => auth()->id(),
    ]);
    if (!$this->isAdminOrRegistrar()) {
    $admins = User::whereIn('role', ['admin', 'registrar'])->get();
    foreach ($admins as $admin) {
        $admin->notify(new FacultyRequestNotification($newFaculty, auth()->user()->name, 'pending'));
    }
}

    $this->logAction($newFaculty->id, 'created', "Registered faculty: {$newFaculty->full_name}");
    $this->showModal = false;
    $this->dispatch('toast', [
    'type' => 'success', 
    'message' => 'Faculty Registered', 
    'detail' => "{$this->full_name} has been added to the registry."
]);
}

    public function deleteSelected()
{
    if (empty($this->selectedFaculty)) return;

    $user = auth()->user();
    if (!in_array($user->role, ['admin', 'registrar', 'dean'])) {
        return;
    }

    $count = 0;
    $faculties = Faculty::whereIn('id', $this->selectedFaculty)->get();
    
    $actorRole = strtoupper(auth()->user()->role); 
    $actorName = auth()->user()->name;

    foreach ($faculties as $faculty) {
    if ($user->role === 'dean') {
        if ($faculty->status !== 'rejected' || $faculty->department !== $user->department) {
            continue; 
        }
        }
        // 1. Notify the original requester
        $admins = User::whereIn('role', ['admin', 'registrar'])->get();
        foreach ($admins as $admin) {
            // We pass $faculty->full_name as a string because it will be deleted soon
            $admin->notify(new FacultyRequestNotification($faculty->full_name, auth()->user()->name, 'deleted'));
        }

        // 2. Capture details for the replacement log
        $name = $faculty->full_name;
        $dept = $faculty->department;
        $status = strtoupper($faculty->status);

        // 3. REMOVE OLD LOGS (Cleans up the "Declined" entries)
        \App\Models\FacultyLog::where('faculty_id', $faculty->id)->delete();

        // 4. Create the NEW log (This triggers the RED color in your sidebar)
        $this->logAction(
            null, 
            'deleted', 
            "{$actorRole} ({$actorName}) removed the {$status} faculty: {$name}",
            $dept
        );

        // 5. Delete this specific record
        $faculty->delete();
        $count++;
    }

    // 6. Reset UI
    $this->reset(['selectedFaculty', 'selectAll', 'confirmingDeletion']);
    
    if ($count > 0) {
        $this->dispatch('toast', [
            'type'    => 'warning', 
            'message' => 'Bulk Deletion Complete', 
            'detail'  => "$count faculty entries have been removed."
        ]);
    }

    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
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
    // 0. SAFETY CHECK: Ensure we actually have an ID to work with
    if (!$this->faculty_id) {
        $this->dispatch('toast', ['type' => 'error', 'message' => 'Error', 'detail' => 'Record ID not found.']);
        return;
    }

    // 1. VALIDATION WITH UNIQUE IGNORE
    // We wrap everything in an array to ensure Laravel processes the Rule::unique correctly.
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

    // 2. FIND AND UPDATE
    $faculty = \App\Models\Faculty::findOrFail($this->faculty_id);
    
    $faculty->update([
        'employee_id' => $this->employee_id,
        'full_name'   => $this->full_name,
        'email'       => $this->email,
        'department'  => $this->department,
    ]);

    // 3. LOG ACTION
    $this->logAction($faculty->id, 'updated', "Modified record: {$faculty->full_name}");

    // 4. BROADCAST NOTIFICATIONS (Admin/Registrar only)
    if ($this->isAdminOrRegistrar()) {
        $actorName = auth()->user()->name;

        // Notify Dept Dean
        $dean = \App\Models\User::where('role', 'dean')
                    ->where('department', $faculty->department)
                    ->first();
        if ($dean) {
            $dean->notify(new \App\Notifications\FacultyRequestNotification($faculty, $actorName, 'edited'));
        }

        // Notify Associate Dean
        $assocDean = \App\Models\User::where('role', 'associate_dean')->first();
        if ($assocDean) {
            $assocDean->notify(new \App\Notifications\FacultyRequestNotification($faculty, $actorName, 'edited'));
        }
    }

    // 5. UI FEEDBACK & REFRESH
    $this->showModal = false;
    
    $this->dispatch('toast', [
        'type' => 'success', 
        'message' => 'Record Updated', 
        'detail' => "Details for {$this->full_name} have been updated successfully."
    ]);

    // Signal Notification Center and Tables to refresh
    $this->dispatch('facultyUpdated')->to(\App\Livewire\NotificationCenter::class);
}
    public function deleteFaculty($id)
{
    $faculty = Faculty::findOrFail($id);
    $actor = auth()->user();
    
    // --- PERMISSION GATE ---
    $isAdminOrRegistrar = in_array($actor->role, ['admin', 'registrar']);
    $isAssociateDean = ($actor->role === 'associate_dean');
    $isDeptDean = ($actor->role === 'dean' && $faculty->department === $actor->department);
    $isRejected = ($faculty->status === 'rejected');

    // Logic: Admin/Registrar can delete anything. 
    // Associate Dean and Dept Deans can ONLY delete if the status is 'rejected'.
    if (!$isAdminOrRegistrar && !(($isAssociateDean || $isDeptDean) && $isRejected)) {
        $this->dispatch('toast', [
            'type' => 'error', 
            'message' => 'Access Denied', 
            'detail' => 'You do not have permission to delete active records.'
        ]);
        return;
    }

    // 1. Capture details before deletion (to avoid the "Deleted Record" crash)
    $name = $faculty->full_name;
    $dept = $faculty->department;
    $status = strtoupper($faculty->status);
    $actorRole = strtoupper($actor->role); 

    // 2. Clear old logs for this specific faculty ID to prevent foreign key errors
    \App\Models\FacultyLog::where('faculty_id', $id)->delete();

    // 3. Log the action in the system audit trail
    $this->logAction(
        null, 
        'deleted', 
        "{$actorRole} ({$actor->name}) removed the {$status} faculty: {$name}",
        $dept
    );

    // 4. Send Notifications
    // We notify other relevant roles that a record was removed
    $targetRoles = ['admin', 'registrar', 'dean', 'associate_dean'];
    
    $recipients = \App\Models\User::whereIn('role', $targetRoles)
        ->where('id', '!=', $actor->id) // Don't notify the person who deleted it
        ->get();

    foreach ($recipients as $recipient) {
        // Pass $name as a string so the notification doesn't try to find the deleted model
        $recipient->notify(new \App\Notifications\FacultyRequestNotification(
            $name, 
            $actor->name, 
            'deleted'
        ));
    }

    // 5. Delete the record
    $faculty->delete();

    // 6. Dispatch Toast notification
    $this->dispatch('toast', [
        'type' => 'warning', 
        'message' => 'Faculty Removed', 
        'detail' => "{$name} has been removed from the system."
    ]);

    // 7. Refresh components
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
        $importedDepartments = []; // To keep track of which deans to notify

        \Illuminate\Support\Facades\DB::transaction(function () use (&$importCount, &$importedDepartments) {
            foreach ($this->importPreview as $data) {
                // Duplicate Protection (Check ID or Email)
                $exists = Faculty::where('employee_id', $data['employee_id'])
                    ->orWhere('email', $data['email'])
                    ->exists();

                if ($exists) continue;

                // 1. Create the Faculty Record
                $faculty = Faculty::create([
                    'employee_id'  => $data['employee_id'],
                    'full_name'    => $data['full_name'],
                    'email'        => $data['email'],
                    'department'   => $data['department'],
                    'status'       => 'approved', 
                    'requested_by' => auth()->id(),
                ]);

                // 2. Log the activity for the sidebar
                $this->logAction(
                    $faculty->id, 
                    'created', 
                    "CREATED BY " . strtoupper(auth()->user()->role) . ": {$faculty->full_name} ({$faculty->department})",
                    $faculty->department
                );

                // 3. Track department for summary notifications
                if (!empty($data['department'])) {
                    $importedDepartments[] = $data['department'];
                }

                $importCount++;
            }
        });

        if ($importCount > 0) {
            $sender = auth()->user()->name;
            $uniqueDepts = array_unique($importedDepartments);

            // 1. Notify Department Deans (Only about their own departments)
            foreach ($uniqueDepts as $deptName) {
                $dean = User::where('role', 'dean')->where('department', $deptName)->first();
                if ($dean) {
                    $dean->notify(new FacultyRequestNotification(
                        "the $deptName Department", 
                        $sender, 
                        'bulk_added'
                    ));
                }
            }

            // 2. Notify Associate Dean (Global oversight)
            $assocDean = User::where('role', 'associate_dean')->first();
            if ($assocDean) {
                $assocDean->notify(new FacultyRequestNotification(
                    "$importCount Faculty Members", 
                    $sender, 
                    'bulk_added'
                ));
            }

            // 3. Signal all NotificationCenter components to refresh
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        }

        // Cleanup UI State
        $this->reset(['importFile', 'importPreview', 'bulkOpen']); 
        $this->dispatch('close-import-modal'); 

        $this->dispatch('toast', [
            'type'    => 'success', 
            'message' => 'Import Successful', 
            'detail'  => "Successfully added $importCount records and notified relevant Deans."
        ]);

    } catch (\Exception $e) {
        $this->dispatch('close-import-modal');
        $this->dispatch('toast', [
            'type'    => 'error', 
            'message' => 'Import Failed', 
            'detail'  => 'Something went wrong while processing the CSV.'
        ]);
    }
}

    public function approveFaculty($id) 
{
    if (!$this->isAdminOrRegistrar()) return;

    $faculty = Faculty::findOrFail($id);
    $faculty->update(['status' => 'approved']);
    
    $this->logAction($faculty->id, 'approved', "Approved entry: {$faculty->full_name}");

    // Notify the Dean who requested this
    $dean = User::find($faculty->requested_by);
    if ($dean) {
        $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'approved'));
    }
   $this->dispatch('toast', [
        'type'    => 'success',
        'message' => 'Request Approved',
        'detail'  => "{$faculty->full_name} is now active in the registry."
    ]);

    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
}
    public function declineFaculty($id) 
{
    if (!$this->isAdminOrRegistrar()) return;

    $faculty = Faculty::findOrFail($id);
    
    // Update status to rejected
    $faculty->update(['status' => 'rejected']);

    // Log the decline action BEFORE sending notifications
    $this->logAction(
        $faculty->id, 
        'rejected', 
        "DECLINED BY " . strtoupper(auth()->user()->role) . ": {$faculty->full_name} ({$faculty->department})",
        $faculty->department
    );
    
    // Notify the Dean who requested the faculty
    $dean = User::find($faculty->requested_by);
    if ($dean) {
        $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'rejected'));
    }
    
    // Dispatch Toast with the Array structure your script expects
    $this->dispatch('toast', [
        'type'    => 'info',
        'message' => 'Request Declined',
        'detail'  => "The registration for {$faculty->full_name} was rejected."
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

