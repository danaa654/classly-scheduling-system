<?php

namespace App\Livewire;

use App\Models\Faculty;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

class ManageFaculty extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '', $filterDepartment = '', $importFile;
    public $faculty_id, $employee_id, $full_name, $email, $department = 'CCS', $status = 'Active';
    public $showModal = false, $bulkOpen = false, $isEditMode = false;

    public function openModal() {
        $this->resetValidation();
        $this->reset(['faculty_id', 'employee_id', 'full_name', 'email', 'isEditMode', 'department']);
        $this->department = 'CCS';
        $this->showModal = true;
    }

    public function saveFaculty() {
        $this->validate([
            'employee_id' => 'required|unique:faculties,employee_id',
            'full_name'   => 'required|string|max:255',
            'department'  => 'required',
        ]);

        Faculty::create([
            'employee_id' => $this->employee_id,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'department'  => $this->department,
            'status'      => $this->status,
        ]);

        $this->showModal = false;
        session()->flash('message', 'Faculty added!');
    }

    public function editFaculty($id) {
        $this->resetValidation();
        $f = Faculty::findOrFail($id);
        $this->faculty_id = $f->id;
        $this->employee_id = $f->employee_id;
        $this->full_name = $f->full_name;
        $this->email = $f->email;
        $this->department = $f->department;
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function updateFaculty() {
        $this->validate([
            'employee_id' => ['required', Rule::unique('faculties')->ignore($this->faculty_id)],
            'full_name'   => 'required|string|max:255',
            'department'  => 'required',
        ]);

        $f = Faculty::find($this->faculty_id);
        $f->update([
            'employee_id' => $this->employee_id,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'department'  => $this->department,
        ]);

        $this->showModal = false;
        $this->isEditMode = false;
    }

    public function deleteFaculty($id) {
        Faculty::find($id)->delete();
    }

    public function importFaculty() {
        $this->validate(['importFile' => 'required|mimes:csv,txt']);
        
        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file); // Skip header

        while (($row = fgetcsv($file)) !== FALSE) {
            Faculty::updateOrCreate(
                ['employee_id' => $row[0]],
                [
                    'full_name' => $row[1] ?? 'Unknown', 
                    'email' => $row[2] ?? null, 
                    'department' => $row[3] ?? 'CCS'
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

    return view('livewire.manage-faculty', [
        'faculties' => Faculty::query()
            ->when(in_array($user->role, ['dean', 'oic']), function($query) use ($user) {
                // This is the "Wall" - it forces the query to stay in their department
                return $query->where('department', $user->department);
            })
            ->latest()
            ->paginate(10)
    ]);
}
}