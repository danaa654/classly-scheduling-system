<?php

namespace App\Livewire;

use App\Models\Faculty;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use App\Notifications\FacultyRequestNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class ManageFaculty extends Component
{
    use WithPagination, WithFileUploads;

    // Filters & UI State
    public $search = '';
    public $filterDepartment = ''; // Empty string means "ALL"
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;
    public $importFile;

    // Form fields
    public $faculty_id; // For editing
    public $employee_id, $full_name, $email, $department = 'CCS';
    
    // Reset pagination when search or filter changes
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterDepartment() { $this->resetPage(); }

    public function setFilter($dept) {
        $this->filterDepartment = ($dept === 'ALL') ? '' : $dept;
    }

    public function openModal() {
        $this->resetValidation();
        $this->reset(['faculty_id', 'employee_id', 'full_name', 'email', 'isEditMode']);
        $this->department = 'CCS';
        $this->showModal = true;
    }

    /**
     * Logic for saving new faculty (includes approval workflow)
     */
    public function saveFaculty() {
        $this->validate([
            'employee_id' => 'required|unique:faculties,employee_id',
            'full_name'   => 'required|string|max:255',
            'department'  => 'required',
        ]);

        // Automatically approve if Admin/Registrar, otherwise set to Pending
        $status = in_array(auth()->user()->role, ['admin', 'registrar']) ? 'approved' : 'pending';

        Faculty::create([
            'employee_id'  => $this->employee_id,
            'full_name'    => $this->full_name,
            'email'        => $this->email,
            'department'   => $this->department,
            'status'       => $status,
            'requested_by' => auth()->id(),
        ]);
        
        if ($status === 'pending') {
            $admins = User::whereIn('role', ['admin', 'registrar'])->get();
            
            // Pass the new faculty model and the current user's name
            Notification::send($admins, new FacultyRequestNotification($newFaculty, auth()->user()->name));
        }

        $this->showModal = false;
        session()->flash('message', ($status === 'approved') ? 'Faculty added!' : 'Faculty request submitted for approval.');
    }

    public function editFaculty($id) {
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

    public function updateFaculty() {
        $this->validate([
            'employee_id' => ['required', Rule::unique('faculties')->ignore($this->faculty_id)],
            'full_name'   => 'required|string|max:255',
            'department'  => 'required',
        ]);

        Faculty::findOrFail($this->faculty_id)->update([
            'employee_id' => $this->employee_id,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'department'  => $this->department,
        ]);

        $this->showModal = false;
        $this->isEditMode = false;
    }

    /**
     * Admin/Registrar Approval Methods
     */
    public function approveFaculty($id) {
        if (in_array(auth()->user()->role, ['admin', 'registrar'])) {
            Faculty::findOrFail($id)->update(['status' => 'approved']);
            session()->flash('message', 'Faculty has been approved.');
        }
    }

    public function declineFaculty($id, $reason = null) {
        if (in_array(auth()->user()->role, ['admin', 'registrar'])) {
            Faculty::findOrFail($id)->update([
                'status' => 'rejected',
                'rejection_reason' => $reason
            ]);
            session()->flash('message', 'Faculty request rejected.');
        }
    }

    public function deleteFaculty($id) {
        Faculty::findOrFail($id)->delete();
    }

    public function importFaculty() {
        $this->validate(['importFile' => 'required|mimes:csv,txt']);
        
        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file); // Skip header row

        while (($row = fgetcsv($file)) !== FALSE) {
            Faculty::updateOrCreate(
                ['employee_id' => $row[0]],
                [
                    'full_name'  => $row[1] ?? 'Unknown', 
                    'email'      => $row[2] ?? null, 
                    'department' => $row[3] ?? 'CCS',
                    'status'     => 'approved' // Bulk imports usually assumed pre-approved
                ]
            );
        }

        fclose($file);
        $this->bulkOpen = false;
        $this->reset('importFile');
    }

    public function render()
    {
        $user = auth()->user();

        $query = Faculty::query()
            // 1. Departmental restriction: Deans only see their own department
            ->when(in_array($user->role, ['dean', 'oic']), function($q) use ($user) {
                return $q->where('department', $user->department);
            })
            // 2. Departmental Filter (Top Buttons)
            ->when($this->filterDepartment, function($q) {
                return $q->where('department', $this->filterDepartment);
            })
            // 3. Search logic
            ->when($this->search, function($q) {
                return $q->where(function($sub) {
                    $sub->where('full_name', 'like', '%' . $this->search . '%')
                        ->orWhere('employee_id', 'like', '%' . $this->search . '%');
                });
            });

        return view('livewire.manage-faculty', [
            'faculties' => $query->latest()->paginate(10)
        ]);
    }
}