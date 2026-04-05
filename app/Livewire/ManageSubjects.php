<?php

namespace App\Livewire;

use App\Models\Subject;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class ManageSubjects extends Component
{
    use WithPagination, WithFileUploads;

    // Table & Functional State
    public $search = '';
    public $filterDepartment = ''; 
    public $importFile;

    // Form State
    public $subject_id;
    public $subject_code;
    public $description;
    public $units = 3;
    public $department = 'CCS';
    
    // UI State
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;

    // Validation Rules
    protected function rules() {
        return [
            'subject_code' => 'required|unique:subjects,subject_code,' . $this->subject_id,
            'description'  => 'required|string|max:255',
            'units'        => 'required|integer|min:1|max:6',
            'department'   => 'required|in:CCS,CTE,COC,SHTM',
        ];
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterDepartment() { $this->resetPage(); }

    public function openModal() {
        $this->resetValidation();
        $this->reset(['subject_id', 'subject_code', 'description', 'isEditMode']);
        $this->units = 3; // Default units
        $this->department = 'CCS'; // Default dept
        $this->showModal = true;
    }

    /**
     * CREATE: Save New Subject
     */
    public function saveSubject() 
    {
        $this->validate();

        Subject::create([
            'subject_code' => strtoupper($this->subject_code), 
            'description'  => $this->description, 
            'units'        => $this->units, 
            'department'   => $this->department
        ]);

        $this->showModal = false;
        $this->reset(['subject_code', 'description', 'units']);
        session()->flash('message', 'Subject added successfully!');
    }

    /**
     * EDIT: Load data into form
     */
    public function editSubject($id) 
    {
        $this->resetValidation();
        $subject = Subject::findOrFail($id);
        
        $this->subject_id   = $subject->id;
        $this->subject_code = $subject->subject_code;
        $this->description  = $subject->description;
        $this->units        = $subject->units;
        $this->department   = $subject->department;
        
        $this->isEditMode = true;
        $this->showModal  = true;
    }

    /**
     * UPDATE: Save changes to existing record
     */
    public function updateSubject()
    {
        $this->validate();

        if ($this->subject_id) {
            $subject = Subject::find($this->subject_id);
            $subject->update([
                'subject_code' => strtoupper($this->subject_code),
                'description'  => $this->description,
                'units'        => $this->units,
                'department'   => $this->department,
            ]);

            $this->showModal = false;
            $this->isEditMode = false;
            $this->reset(['subject_code', 'description', 'subject_id']);
            
            session()->flash('message', 'Subject updated successfully!');
        }
    }

    /**
     * DELETE: Remove Subject
     */
    public function deleteSubject($id) 
    {
        Subject::findOrFail($id)->delete();
        session()->flash('message', 'Subject removed from catalog.');
    }

    /**
     * BULK: Import from CSV
     */
    public function importSubjects() {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);
        
        $file = fopen($this->importFile->getRealPath(), 'r');
        fgetcsv($file); // Skip header row

        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            if(isset($row[0])) {
                Subject::updateOrCreate(
                    ['subject_code' => strtoupper($row[0])],
                    [
                        'description' => $row[1] ?? 'No Description',
                        'units'       => $row[2] ?? 3,
                        'department'  => $row[3] ?? 'CCS',
                    ]
                );
                $count++;
            }
        }
        fclose($file);
        $this->bulkOpen = false;
        $this->reset('importFile');
        session()->flash('message', "Bulk Import: $count subjects added/updated.");
    }

    public function render() {
        $query = Subject::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subject_code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterDepartment) {
            $query->where('department', $this->filterDepartment);
        }

        return view('livewire.manage-subjects', [
            'subjects' => $query->latest()->paginate(10)
        ]);
    }
}