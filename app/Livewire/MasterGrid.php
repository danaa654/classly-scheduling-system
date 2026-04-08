<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Livewire\WithPagination;

class MasterGrid extends Component
{
    use WithPagination;

    // Filtering properties
    public $searchSubject = ''; 
    public $selectedDept = '';
    public $selectedYear = '';
    public $selectedMajor = '';
    
    // UI State properties
    public $selectedRoomId = null;
    public $selectedRoomName = null;
    public $selectedSection = ''; 

    // Grid properties
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    public $timeSlots = [
        '07:30 AM - 09:00 AM', '09:00 AM - 10:30 AM', '10:30 AM - 12:00 PM',
        '01:00 PM - 02:30 PM', '02:30 PM - 04:00 PM', '04:00 PM - 05:30 PM'
    ];

    // Reset page on search/filter change
    public function updatedSearchSubject() { $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedMajor() { $this->resetPage(); }

    // Reset major if department changes (Logic from ManageSubject)
    public function updatedSelectedDept()
    {
        $this->selectedMajor = '';
        $this->resetPage();
    }

    public function render()    
    {
        $service = new ScheduleService();

        // APPLYING THE WORKING FILTER LOGIC FROM MANAGESUBJECT
        $subjects = Subject::query()
            ->when($this->searchSubject, function($query) {
                $query->where(function($q) {
                    $q->where('subject_code', 'like', "%{$this->searchSubject}%")
                      ->orWhere('edp_code', 'like', "%{$this->searchSubject}%")
                      ->orWhere('description', 'like', "%{$this->searchSubject}%");
                });
            })
            // 1. Department Filter (Checks start of EDP Code)
            ->when($this->selectedDept, function($query) {
                $query->where('edp_code', 'like', $this->selectedDept . '-%');
            })
            // 2. Year Filter (Checks specific segment of EDP Code)
            ->when($this->selectedYear, function($query) {
                $query->where('edp_code', 'like', '%-%-' . $this->selectedYear . '%');
            })
            // 3. Major Filter (Checks start of Subject Code)
            ->when($this->selectedMajor, function($query) {
                $query->where('subject_code', 'like', $this->selectedMajor . '%');
            })
            ->when($this->selectedMajor, function($query) {
            // This ensures the dropdown filter for Majors works
            $query->where('subject_code', 'like', $this->selectedMajor . '%');
            })  
            ->orderBy('edp_code', 'asc')
            ->get()
            ->map(function($subject) {
            // STEP 1: Normalize the 'type' from your ManageSubject data
            $status = strtolower(trim($subject->type ?? 'minor'));
            
            // STEP 2: Create a simple boolean for the Blade view
            $subject->is_major_type = ($status === 'major');
            
            return $subject;
        });

        $rooms = Room::all()->map(function($room) use ($service) {
            $room->utilization = $service->getRoomLoad($room->id);
            return $room;
        });

        return view('livewire.master-grid', [
        'subjects' => $subjects,
        'rooms' => \App\Models\Room::all(),
        'schedules' => \App\Models\Schedule::with(['subject', 'room'])->get()
        ]);
    }

    public function selectRoom($id)
    {
        $room = Room::find($id);
        if ($room) {
            $this->selectedRoomId = $id;
            $this->selectedRoomName = $room->room_name;
        }
    }
}