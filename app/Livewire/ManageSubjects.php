<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
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
    public $selectedSection = '';
    public $selectedMajor = '';
    

    // Form Fields - REMOVED $department
    public $subjectId, $edp_code, $subject_code, $description, $units, $type = 'Major';

    // CSV File
    public $importFile;

    public function updatedSearch() { $this->resetPage(); }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['subjectId', 'edp_code', 'subject_code', 'description', 'units', 'type', 'isEditMode']);
        $this->type = 'Major';
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
        
        $this->showModal = true;
    }

    public function saveSubject()
    {
        $this->validate([
            'edp_code' => 'required|unique:subjects,edp_code,' . $this->subjectId,
            'subject_code' => 'required',
            'description' => 'required',
            'units' => 'required|numeric',
            'type' => 'required|in:Major,Minor',
        ]);

        try {
            Subject::updateOrCreate(
                ['id' => $this->subjectId],
                [
                    'edp_code'     => strtoupper($this->edp_code),
                    'subject_code' => strtoupper($this->subject_code),
                    'description'  => $this->description,
                    'units'        => $this->units,
                    'type'         => $this->type,
                    // Optionally explicitly nullify department if column still exists
                    'department'   => null, 
                ]
            );

            $this->showModal = false;
            $this->reset(['subjectId', 'edp_code', 'subject_code', 'description', 'units', 'type']);
            session()->flash('message', 'Subject saved successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function importSubjects()
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);
        $file = fopen($this->importFile->getRealPath(), 'r');
        $header = true;

        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            if ($header) { $header = false; continue; }
            if (empty($row[0])) continue;

            // Mapping: 0:EDP, 1:Subj, 2:Desc, 3:Units, 5:Type (Skipping 4:Dept)
            // Inside importSubjects()
Subject::updateOrCreate(
    ['edp_code' => strtoupper(trim($row[0]))],
    [
        'subject_code' => strtoupper(trim($row[1] ?? '')),
        'description'  => trim($row[2] ?? ''),
        'units'        => (int)($row[3] ?? 3),
        // Ensure this points to column index 5 (F) from your spreadsheet
        'type'         => !empty($row[5]) ? ucfirst(strtolower(trim($row[5]))) : 'Minor',
    ]
);
        }
        fclose($file);
        $this->reset(['importFile', 'bulkOpen']);
    }

    public function deleteSubject($id)
    {
        Subject::findOrFail($id)->delete();
    }


    public function updatedSelectedDept() { $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedSection() { $this->resetPage();}
    
    
    // app/Livewire/ManageSubjects.php

public function render()
{
    return view('livewire.manage-subjects', [
        'subjects' => Subject::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('subject_code', 'like', "%{$this->search}%")
                      ->orWhere('edp_code', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            // 1. Department Filter (CTE, SHTM, etc.)
            ->when($this->selectedDept, function($query) {
                $query->where('edp_code', 'like', $this->selectedDept . '-%');
            })
            // 2. Year Filter (Matches CTE-ED-201 where 2 is the year)
            ->when($this->selectedYear, function($query) {
                // Matches the first digit of the last segment of the EDP code
                $query->where('edp_code', 'like', '%-%-' . $this->selectedYear . '%');
            })
            // 3. Major Filter Fix (Matches HM-, TM-, FB-, etc.)
            ->when($this->selectedMajor, function($query) {
                // This targets the subject_code specifically (e.g., TM-101)
                $query->where('subject_code', 'like', $this->selectedMajor . '%');
            })
            ->orderBy('edp_code', 'asc')
            ->paginate(10)
    ]);
}
}