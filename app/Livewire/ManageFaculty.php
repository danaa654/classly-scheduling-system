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

    public function updatedSelectAll($value)
{
    if ($value) {
        $this->selectedFaculty = Faculty::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->when(!$this->isAdminOrRegistrar(), function($q) {
                // DEAN RESTRICTION: Only allow selecting 'rejected' ones for deletion
                $q->where('department', auth()->user()->department)
                  ->where('status', 'rejected');
            })
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();
            
        // If a Dean tried to select all but nothing is rejected, give them a hint
        if(!$this->isAdminOrRegistrar() && empty($this->selectedFaculty)) {
             $this->dispatch('swal', title: 'No Records Selected', text: 'Deans can only bulk-delete rejected records.', icon: 'info');
             $this->selectAll = false;
        }
    } else {
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

    private function isAdminOrRegistrar()
    {
        return in_array(auth()->user()->role, ['admin', 'registrar']);
    }
    private function isGlobalViewer()
{
    return in_array(auth()->user()->role, ['admin', 'registrar', 'assoc_dean']);
}

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterDepartment() { $this->resetPage(); }

    // --- MODAL OPERATIONS ---
    // --- MODAL OPERATIONS ---
public function openModal() 
{
    // 1. Clear all fields and validation errors
    $this->reset(['employee_id', 'full_name', 'email', 'department', 'faculty_id']);
    $this->resetValidation();
    
    // 2. Set to New Registration mode
    $this->isEditMode = false;

    // 3. Logic for "Next ID" (e.g., EMP001, EMP002)
    $lastFaculty = \App\Models\Faculty::orderBy('id', 'desc')->first();
    
    // Extract numbers only, increment, and pad with zeros
    $lastNum = $lastFaculty ? (int)filter_var($lastFaculty->employee_id, FILTER_SANITIZE_NUMBER_INT) : 0;
    $this->employee_id = "EMP" . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);

    // 4. Auto-lock Department if the user is a Dean
    if (auth()->user()->role === 'dean') {
        $this->department = auth()->user()->department;
    }

    // 5. Trigger UI visibility (matches your wire:click and @click)
    $this->showModal = true; 
}

// Updated Rules for stricter validation
protected $rules = [
    'employee_id' => 'required|unique:faculties,employee_id',
    'full_name'   => 'required|min:5|regex:/(\s)/', // Requires First and Last name
    'email'       => 'required|email|contains:@',
    'department'  => 'required',
];

   public function saveFaculty() 
{
    $this->validate([
        'employee_id' => 'required|unique:faculties,employee_id',
        // Rule order: Required first, then Unique check, then formatting
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
        $this->email       = $f->email;
        $this->department  = $f->department;
        $this->isEditMode = true;
        $this->showModal  = true;
    }

    public function updateFaculty() 
    {
        $this->validate([
        'employee_id' => 'required|unique:faculties,employee_id',
        'full_name'   => 'required|min:5|regex:/(\s)/|unique:faculties,full_name', 
        'email'       => 'required|email|contains:@|unique:faculties,email',
        'department'  => 'required',
    ], [
        // Custom Error Messages for your Issue #2
        'full_name.unique' => 'That name is already being used.',
        'email.unique'     => 'That email address is already being used.',
        'full_name.regex'  => 'Please enter your complete full name (First and Last).',
    ]);

        $faculty = Faculty::findOrFail($this->faculty_id);
        $faculty->update([
            'employee_id' => $this->employee_id,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'department'  => $this->department,
        ]);

        $this->logAction($faculty->id, 'updated', "Modified record: {$faculty->full_name}");

        if ($this->isAdminOrRegistrar()) {
            $dean = User::where('role', 'dean')->where('department', $faculty->department)->first();
            if ($dean) {
                $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'edited'));
            }
        }

        $this->showModal = false;
        $this->dispatch('toast', [
            'type' => 'success', 
            'message' => 'Record Updated', 
            'detail' => 'The faculty details have been successfully modified.'
        ]);
        $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
    }

    public function deleteFaculty($id)
{
    // Ensure only authorized roles can perform the deletion
    if (!in_array(auth()->user()->role, ['admin', 'registrar'])) return;

    $faculty = Faculty::findOrFail($id);
    
    // 1. Capture details before deletion
    $name = $faculty->full_name;
    $dept = $faculty->department;
    $status = strtoupper($faculty->status);
    $actor = auth()->user();
    $actorRole = strtoupper($actor->role); 

    // 2. Clear old logs for this specific faculty ID
    \App\Models\FacultyLog::where('faculty_id', $id)->delete();

    // 3. Log the action in the system logs
    $this->logAction(
        null, 
        'deleted', 
        "{$actorRole} ({$actor->name}) removed the {$status} faculty: {$name}",
        $dept
    );

    // 4. Send Notifications to Database for other roles
    // We target admin, registrar, dean, and assistant dean
    $targetRoles = ['admin', 'registrar', 'dean', 'assistant dean'];
    
    $recipients = \App\Models\User::whereIn('role', $targetRoles)
        ->where('id', '!=', $actor->id) // DO NOT notify the person doing the work
        ->get();

    foreach ($recipients as $recipient) {
        // Assuming you have a Notification model or a relation
        // Replace this with your actual notification creation logic
        \App\Models\Notification::create([
            'user_id' => $recipient->id,
            'title' => 'Faculty Deleted',
            'message' => "{$name} was removed by {$actor->name} ({$actorRole})",
            'type' => 'deletion',
            'is_read' => false,
        ]);
    }

    // 5. Delete the record
    $faculty->delete();

    // 6. Dispatch Toast (Using Livewire v3 named parameters)
    $this->dispatch('toast', 
        type: 'warning', 
        message: 'Faculty Removed', 
        description: "{$name} has been removed and logs updated."
    );

    // 7. Refresh the notification bell/center
    $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
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

        // --- NOTIFICATION LOGIC ---
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
    // If not a global viewer (e.g., a Dean), lock them to their own department
    if (!$this->isGlobalViewer()) { 
        $this->filterDepartment = $user->department; 
    }

    // 2. PENDING REQUESTS
    $pendingRequests = Faculty::where('status', 'pending')
        ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
            // Deans and Assoc Deans only see pending requests they personally created
            // They cannot see or approve other people's pending requests
            return $q->where('requested_by', $user->id);
        })->orderBy('created_at', 'desc')->get();

    // 3. MAIN FACULTY TABLE
    $faculties = Faculty::query()
        ->whereIn('status', ['approved', 'rejected']) 
        ->when(!$this->isGlobalViewer(), function ($q) use ($user) {
            // If they aren't a Global Viewer, only show their department
            return $q->where('department', $user->department);
        })
        ->when($this->filterDepartment, function ($q) {
            // Allow Global Viewers to use the dropdown to filter by any department
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
                // Registrar, Admin, and Assoc. Dean see the global audit trail
                $q->whereRaw('1=1'); 
            } else {
                // Deans only see their own actions or actions within their department
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

