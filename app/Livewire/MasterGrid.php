<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
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
    #[Url]
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

    public function updatedSelectedDept()
    {
        $this->selectedMajor = '';
        $this->resetPage();
    }

    /**
     * FIX: The Logic for assigning a subject
     */
    public function assignSubject($subjectId, $day, $timeSlot)
{
    // 1. Guard: Room must be selected
    if (!$this->selectedRoomId) {
        $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a room first!']);
        return;
    }

    // 2. Split the time string
    $parts = explode(' - ', $timeSlot);
    $startTime = \Carbon\Carbon::createFromFormat('h:i A', trim($parts[0]))->format('H:i:s');
    $endTime = \Carbon\Carbon::createFromFormat('h:i A', trim($parts[1] ?? ''))->format('H:i:s');
    
    // 3. Unit Limit Check
    $subject = \App\Models\Subject::find($subjectId);
    $scheduledCount = \App\Models\Schedule::where('subject_id', $subjectId)->count();
    if (($scheduledCount * 1.5) >= $subject->units) {
        $this->dispatch('notify', ['type' => 'error', 'message' => 'Subject is already fully scheduled!']);
        return;
    }

    // 4. Conflict Check (Same Room, Same Time, Same Day)
    $exists = \App\Models\Schedule::where('room_id', $this->selectedRoomId)
        ->where('day', $day)
        ->where('start_time', $startTime)
        ->where('end_time', $endTime)
        ->exists();

    if ($exists) {
        $this->dispatch('notify', ['type' => 'warning', 'message' => 'This slot is already occupied!']);
        return;
    }

    // 5. Create the Record
    \App\Models\Schedule::create([
        'subject_id' => $subjectId,
        'room_id'    => $this->selectedRoomId,
        'user_id'    => auth()->id() ?? 1, 
        'day'        => $day,
        'start_time' => $startTime,
        'end_time'   => $endTime,
        'section'    => $this->selectedSection ?: 'A',
    ]);

    $this->dispatch('notify', ['type' => 'success', 'message' => 'Successfully assigned!']);
}
    
    public function removeAssignment($id)
    {
        Schedule::destroy($id);
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Schedule removed.']);
    }

    public function selectRoom($id)
    {
        $room = Room::find($id);
        if ($room) {
            $this->selectedRoomId = $id;
            $this->selectedRoomName = $room->room_name;
        }
    }

    public function render()    
    {
        $service = new ScheduleService();

        // Subject Query Logic
        $subjects = Subject::query()
            ->when($this->searchSubject, function($query) {
                $query->where(function($q) {
                    $q->where('subject_code', 'like', "%{$this->searchSubject}%")
                      ->orWhere('edp_code', 'like', "%{$this->searchSubject}%")
                      ->orWhere('description', 'like', "%{$this->searchSubject}%");
                });  
            })
            ->when($this->selectedDept, function($query) {
                $query->where('edp_code', 'like', $this->selectedDept . '-%');
            })
            ->when($this->selectedYear, function($query) {
                $query->where('edp_code', 'like', '%-%-' . $this->selectedYear . '%');
            })
            ->when($this->selectedMajor, function($query) {
                $query->where('subject_code', 'like', $this->selectedMajor . '%');
            })
            ->orderBy('edp_code', 'asc')
            ->get()
            ->map(function($subject) {
                $status = strtolower(trim($subject->type ?? 'minor'));
                $subject->is_major_type = ($status === 'major');
                $subject->scheduled_hours = \App\Models\Schedule::where('subject_id', $subject->id)->count() * 1.5;
                return $subject;
            })
            ->filter(function($subject) {
            return $subject->scheduled_hours < $subject->units;
            });

        $rooms = Room::all()->map(function($room) use ($service) {
            $room->utilization = $service->getRoomLoad($room->id);
            return $room;
        });

        // FIX: Only show schedules for the CURRENTLY SELECTED ROOM
        $schedules = Schedule::with(['subject', 'room'])
            ->when($this->selectedRoomId, function($query) {
                $query->where('room_id', $this->selectedRoomId);
            })
            ->get();

        return view('livewire.master-grid', [
            'subjects' => $subjects,
            'rooms' => $rooms,
            'schedules' => $schedules
        ]);
    }
}