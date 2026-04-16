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

    // Form fields
    public $faculty_id; 
    public $employee_id, $full_name, $email, $department;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => ''],
    ];

    // --- ACTIVITY LOG HELPER ---
    private function logAction($facultyId, $action, $description) 
{
    // Verification check to ensure we don't log null IDs
    if (!$facultyId) {
        return;
    }

    FacultyLog::create([
        'faculty_id'  => $facultyId,
        'user_id'     => auth()->id(), // Records who performed the action
        'action'      => $action,      // e.g., 'created', 'updated', 'imported'
        'description' => $description, // e.g., 'Batch Imported: John Doe'
    ]);
}

    private function isAdminOrRegistrar()
    {
        return in_array(auth()->user()->role, ['admin', 'registrar']);
    }

    // --- SEARCH/FILTER UPDATES ---
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterDepartment() { $this->resetPage(); }

    // --- MODAL OPERATIONS ---
    public function openModal() 
    {
        $this->resetValidation();
        $this->reset(['faculty_id', 'employee_id', 'full_name', 'email', 'isEditMode']);
        
        // Auto-assign department if user is a Dean
        $this->department = $this->isAdminOrRegistrar() ? '' : auth()->user()->department;
        $this->showModal = true;
    }

    public function saveFaculty() 
    {
        $this->validate([
            'employee_id' => 'required|unique:faculties,employee_id',
            'full_name'   => 'required|string|max:255',
            'department'  => 'required',
            'email'       => 'nullable|email',
        ]);

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
        
        if ($status === 'pending') {
            $registrars = User::whereIn('role', ['admin', 'registrar'])->get();
            Notification::send($registrars, new FacultyRequestNotification($newFaculty, auth()->user()->name, 'pending'));
        }

        $this->showModal = false;
        $this->dispatch('swal', title: $status === 'approved' ? 'Faculty Added!' : 'Request Submitted!', icon: 'success');
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
            'employee_id' => ['required', Rule::unique('faculties')->ignore($this->faculty_id)],
            'full_name'   => 'required|string|max:255',
            'department'  => 'required',
        ]);

        $faculty = Faculty::findOrFail($this->faculty_id);
        $faculty->update([
            'employee_id' => $this->employee_id,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'department'  => $this->department,
        ]);

        $this->logAction($faculty->id, 'updated', "Modified record: {$faculty->full_name}");

        // Notify Dean if changes were made by Admin/Registrar
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
        $faculty = Faculty::find($id);
        if (!$faculty) return;

        $name = $faculty->full_name;
        $this->logAction($id, 'deleted', "Removed record: {$name}");
        $faculty->delete();
        
        $this->dispatch('swal', title: 'Faculty Removed', icon: 'warning');
    }

    // --- BATCH IMPORT LOGIC ---
    public function updatedImportFile() 
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:1024']);
        
        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        array_shift($data); // Remove Header

        $this->importPreview = [];
        foreach($data as $row) {
            if(empty($row[0])) continue;
            
            $exists = Faculty::where('employee_id', $row[0])->exists();
            $this->importPreview[] = [
                'employee_id' => $row[0],
                'full_name'   => $row[1] ?? 'Unnamed',
                'email'       => $row[2] ?? null,
                'department'  => $row[3] ?? ($this->filterDepartment ?: 'CCS'),
                'error'       => $exists ? 'Duplicate ID' : null
            ];
        }
    }

    public function processImport() 
    {
        $validData = collect($this->importPreview)->whereNull('error');

        foreach($validData as $row) {
            $faculty = Faculty::create([
                'employee_id'  => $row['employee_id'],
                'full_name'    => $row['full_name'],
                'email'        => $row['email'],
                'department'   => $row['department'],
                'status'       => 'approved',
                'requested_by' => auth()->id()
            ]);

            $this->logAction($faculty->id, 'imported', "Batch Imported: {$faculty->full_name}");
        }

        $this->reset(['importPreview', 'bulkOpen', 'importFile']);
        $this->dispatch('swal', title: 'Bulk Process Completed', icon: 'success');
    }

    // --- APPROVAL WORKFLOW ---
    public function approveFaculty($id) 
    {
        if (!$this->isAdminOrRegistrar()) return;

        $faculty = Faculty::findOrFail($id);
        $faculty->update(['status' => 'approved']);

        $this->logAction($faculty->id, 'approved', "Approved entry: {$faculty->full_name}");

        $dean = User::find($faculty->requested_by);
        if ($dean) {
            $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'approved'));
        }

        $this->dispatch('swal', title: 'Request Approved', icon: 'success');
    }

    public function declineFaculty($id) 
    {
        $faculty = Faculty::findOrFail($id);
        
        if ($this->isAdminOrRegistrar()) {
            $faculty->update(['status' => 'rejected']);
            $this->logAction($faculty->id, 'rejected', "Rejected entry: {$faculty->full_name}");
            
            $dean = User::find($faculty->requested_by);
            if ($dean) {
                $dean->notify(new FacultyRequestNotification($faculty, auth()->user()->name, 'rejected'));
            }
        } elseif ($faculty->requested_by == auth()->id() && $faculty->status == 'pending') {
            $faculty->delete(); // Requester cancels
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

        /** * FIX APPLIED HERE:
         * If the user is a Dean, we add a 'where' clause for their department.
         * If they are Admin/Registrar, they still get everything.
         */
        $data = Faculty::whereIn('status', ['approved', 'rejected'])
            ->when(!$this->isAdminOrRegistrar(), function($query) use ($user) {
                return $query->where('department', $user->department);
            })
            ->get();

        foreach ($data as $row) {
            fputcsv($file, [
                $row->employee_id, 
                $row->full_name, 
                $row->email, 
                $row->department, 
                ucfirst($row->status)
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function render()
{
    $user = auth()->user();

    if (!$this->isAdminOrRegistrar()) {
        $this->filterDepartment = $user->department;
    }
    $pendingRequests = Faculty::where('status', 'pending')
        ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
            // Filter by department or the specific requester
            return $q->where('department', $user->department)
                     ->where('requested_by', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();

    $faculties = Faculty::query()
        ->whereIn('status', ['approved', 'rejected']) 
        ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
            // Deans only see faculty within their own department
            return $q->where('department', $user->department);
        })
        ->when($this->filterDepartment, function ($q) {
            // Global filter (used by Admin/Registrar)
            return $q->where('department', $this->filterDepartment);
        })
        ->when($this->search, function ($q) {
            $q->where(function ($sub) {
                $sub->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('employee_id', 'like', "%{$this->search}%");
            });
        })
        ->orderBy('employee_id', 'asc')
        ->paginate(10);

    $recentLogs = FacultyLog::with(['user', 'faculty']) // Eager load both for better performance
        ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
            // Deans only see logs related to their department's faculty
            return $q->whereHas('faculty', function ($f) use ($user) {
                $f->where('department', $user->department);
            });
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