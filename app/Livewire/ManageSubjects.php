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

    // Form Fields
    public $subjectId;
    public $edp_code;
    public $subject_code;
    public $description;
    public $department;
    public $units;
    public $type = 'Major';
    public $duration_hours = 3; // Default to 3 hours

    // CSV File
    public $importFile;

    // Reset pagination when search or filters change
    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedDept() { $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedSection() { $this->resetPage(); }
    public function updatedSelectedMajor() { $this->resetPage(); }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['subjectId', 'edp_code', 'subject_code', 'description', 'units', 'type', 'isEditMode']);
        $this->type = 'Major';
        $this->duration_hours = 3; // Reset to default
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
        
        $this->showModal = true;
    }

    public function saveSubject()
    {
        $this->validate([
            'edp_code' => 'required|unique:subjects,edp_code,' . $this->subjectId,
            'subject_code' => 'required',
            'description' => 'required',
            'department' => 'required',
            'units' => 'required|numeric',
            'type' => 'required|in:Major,Minor',
            'duration_hours' => 'required|numeric|min:1|max:10',
        ]);

        try {
            Subject::updateOrCreate(
                ['id' => $this->subjectId],
                [
                    'edp_code'       => strtoupper($this->edp_code),
                    'subject_code'   => strtoupper($this->subject_code),
                    'description'    => $this->description,
                    'units'          => $this->units,
                    'type'           => $this->type,
                    'duration_hours' => $this->duration_hours,
                   'department' => $this->department,
                ]
            );

            $this->showModal = false;
            $this->reset(['subjectId', 'edp_code', 'subject_code', 'description', 'units', 'type', 'duration_hours']);
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

        // SMART MAPPING: Check if Column F or G contains "hrs"
        $col5 = trim($row[5] ?? '');
        $col6 = trim($row[6] ?? '');

        if (str_contains(strtolower($col5), 'hrs')) {
            $rawDuration = $col5;
            $rawType = $col6;
        } else {
            $rawDuration = $col6;
            $rawType = $col5;
        }

        $cleanDuration = (int) filter_var($rawDuration, FILTER_SANITIZE_NUMBER_INT);
        $finalType = !empty($rawType) ? ucfirst(strtolower($rawType)) : 'Major';

        \App\Models\Subject::updateOrCreate(
            ['edp_code' => strtoupper(trim($row[0]))],
            [
                'subject_code'   => strtoupper(trim($row[1] ?? '')),
                'description'    => trim($row[2] ?? ''),
                'units'          => (int)($row[3] ?? 3),
                'department'     => trim($row[4] ?? ''),
                'duration_hours' => $cleanDuration > 0 ? $cleanDuration : 3,
                'type'           => $finalType,
            ]
        );
    }
    fclose($file);
    $this->reset(['importFile', 'bulkOpen']);
}
    public function deleteSubject($id)
    {
        Subject::findOrFail($id)->delete();
        session()->flash('message', 'Subject deleted.');
    }

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
                    $query->where('edp_code', 'like', '%-%-' . $this->selectedYear . '%');
                })
                // 3. Major Filter (Matches HM-, TM-, FB-, etc.)
                ->when($this->selectedMajor, function($query) {
                    $query->where('subject_code', 'like', $this->selectedMajor . '%');
                })
                ->orderBy('edp_code', 'asc')
                ->paginate(10)
        ]);
    }
}