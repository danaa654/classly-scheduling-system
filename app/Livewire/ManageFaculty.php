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

    // Create logic remains the same...
    $status = $this->isAdminOrRegistrar() ? 'approved' : 'pending';

    $newFaculty = Faculty::create([
        'employee_id'  => $this->employee_id,
        'full_name'    => $this->full_name,
        'email'        => $this->email,
        'department'   => $this->department,
        'status'       => $status,
        'requested_by' => auth()->id(),
    ]);

    $this->logAction($newFaculty->id, 'created', "Registered faculty: {$newFaculty->full_name}");
    
    $this->showModal = false;
    $this->dispatch('swal', title: 'Faculty Registered', icon: 'success');
}
    
    public function deleteSelected()
    {
        if (empty($this->selectedFaculty)) return;

        $count = count($this->selectedFaculty);
        $faculties = Faculty::whereIn('id', $this->selectedFaculty)->get();

        foreach ($faculties as $faculty) {
            // Notify Deans for each record being removed in the bulk action
            $dean = User::find($faculty->requested_by);
            if ($dean) {
                $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'deleted'));
            }
            
            // Log individual deletion in the activity log
            $this->logAction($faculty->id, 'deleted', "Bulk deleted: {$faculty->full_name}");
        }

        // Perform the actual database deletion
        Faculty::whereIn('id', $this->selectedFaculty)->delete();

        // Log the overall Bulk action
        FacultyLog::create([
            'user_id' => auth()->id(),
            'action' => 'Bulk Delete',
            'description' => "Deleted $count records from " . auth()->user()->department . " registry.",
            'faculty_id' => null, 
        ]);

        // Reset UI state
        $this->reset(['selectedFaculty', 'selectAll', 'confirmingDeletion']);
        
        $this->dispatch('swal', title: "$count Records Removed", icon: 'warning');
        session()->flash('message', "Successfully removed $count records.");
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
        $this->dispatch('swal', title: 'Record Updated', icon: 'success');
    }

    public function deleteFaculty($id)
{
    if (!$this->isAdminOrRegistrar()) return;

    $faculty = Faculty::findOrFail($id);
    $dept = $faculty->department; // Capture the department (CCS, CTE, etc.)
    $name = $faculty->full_name;
    $deanId = $faculty->requested_by;

    // 1. Notify
    $dean = User::find($deanId);
    if ($dean) {
        $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'deleted'));
    }

    // 2. Log with Department info so the Dean can see it in "Recent Activity"
    $this->logAction($faculty->id, 'deleted', "Deleted faculty: $name", $dept);

    // 3. Delete
    $faculty->delete();

    $this->dispatch('swal', title: 'Faculty Deleted', icon: 'warning');
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
        session()->flash('error', "🚨 Wrong File Type: This is a $type file. Please upload a Faculty CSV.");
        $this->reset(['importFile', 'importPreview']);
        return;
    }

    // Strict Header Check
    $required = ['employee_id', 'full_name'];
    foreach($required as $key) {
        if (!in_array($key, $headers)) {
            session()->flash('error', "🚨 Invalid Format: Your file is missing the '$key' column. This does not look like a Faculty file.");
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
}
 public function processImport()
{
    try {
        $importCount = 0;
        foreach ($this->importPreview as $data) {
            // Check for duplicates before creating to avoid crashes
            $exists = Faculty::where('full_name', $data['full_name'])
                ->orWhere('email', $data['email'])
                ->exists();

            if ($exists) continue;

            $faculty = Faculty::create([
                'employee_id'  => $data['employee_id'],
                'full_name'    => $data['full_name'],
                'email'        => $data['email'],
                'department'   => $data['department'],
                'status'       => 'approved', 
                'requested_by' => auth()->id(), // Essential for Issue #3 (Delete Notifications)
            ]);

            // Fix for Issue #1 & #4: Create a specific log for the Department's Recent Activity
            $this->logAction($faculty->id, 'created', "Imported faculty: {$faculty->full_name}");
            $importCount++;
        }

        $this->reset(['importFile', 'importPreview', 'bulkOpen']); 
        $this->dispatch('close-import-modal'); 
        $this->dispatch('swal', title: "Imported $importCount Records", icon: 'success');

    } catch (\Exception $e) {
        $this->dispatch('close-import-modal');
        session()->flash('error', "Database Error: " . $e->getMessage());
    }
}
    public function approveFaculty($id) 
    {
        if (!$this->isAdminOrRegistrar()) return;

        $faculty = Faculty::findOrFail($id);
        $faculty->update(['status' => 'approved']);
        $this->logAction($faculty->id, 'approved', "Approved entry: {$faculty->full_name}");

        // Notify the Dean who requested this faculty
        $dean = User::find($faculty->requested_by);
        if ($dean) {
            $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'approved'));
        }
        
        $this->dispatch('swal', title: 'Request Approved', icon: 'success');
    }

    public function declineFaculty($id) 
    {
        if (!$this->isAdminOrRegistrar()) return;

        $faculty = Faculty::findOrFail($id);
        $faculty->update(['status' => 'rejected']);
        $this->logAction($faculty->id, 'rejected', "Rejected entry: {$faculty->full_name}");
        
        $dean = User::find($faculty->requested_by);
        if ($dean) {
            $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'rejected'));
        }
        
        $this->dispatch('swal', title: 'Action Recorded', icon: 'info');
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
        if (!$this->isAdminOrRegistrar()) { $this->filterDepartment = $user->department; }

        $pendingRequests = Faculty::where('status', 'pending')
            ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
                return $q->where('department', $user->department)->where('requested_by', $user->id);
            })->orderBy('created_at', 'desc')->get();

        $faculties = Faculty::query()
            ->whereIn('status', ['approved', 'rejected']) 
            ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
                return $q->where('department', $user->department);
            })
            ->when($this->filterDepartment, function ($q) {
                return $q->where('department', $this->filterDepartment);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('employee_id', 'like', "%{$this->search}%");
                });
            })->orderBy('employee_id', 'asc')->paginate(10);

            $recentLogs = FacultyLog::with(['user', 'faculty'])
                ->where(function ($q) use ($user) {
                    if ($this->isAdminOrRegistrar()) {
                        $q->whereRaw('1=1'); // Registrar sees all
                    } else {
                        // DEAN: Show logs where they did the action OR the faculty belongs to their department
                        $q->where('user_id', $user->id)
                        ->orWhereHas('faculty', function ($f) use ($user) {
                            $f->where('department', $user->department);
                        });
                    }
                })
                ->latest()
                ->take(10)
                ->get();

                    return view('livewire.manage-faculty', [
            'faculties' => $faculties,
            'pendingRequests' => $pendingRequests,
            'recentLogs' => $recentLogs
        ]);
    }
}
