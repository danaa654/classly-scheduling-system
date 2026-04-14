<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Services\ScheduleService;
use Livewire\WithPagination;
use Carbon\Carbon;

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
    public $selectedSection = 'A'; 
    public $activeSubjectId = null; // Currently "held" subject for scheduling

    // Grid Config
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    public $startTime = '07:00'; 
    public $endTime = '18:00';   
    public $interval = 60; // 1 hour slots

    /**
     * Initialize settings from database or defaults
     */
    public function mount()
    {
        $this->startTime = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->endTime = Setting::where('key', 'end_time')->first()?->value ?? '18:00';
    }

    // Reset page on search/filter change
    public function updatedSearchSubject() { $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedMajor() { $this->resetPage(); }
    public function updatedSelectedDept() { $this->selectedMajor = ''; $this->resetPage(); }

    /**
     * Sets the subject that is currently being "held" to be scheduled
     */
    public function selectSubject($id)
    {
        if ($this->activeSubjectId === $id) {
            $this->activeSubjectId = null;
        } else {
            $this->activeSubjectId = $id;
        }
    }

    /**
     * Save global grid settings
     */
    public function saveSettings()
    {
        Setting::updateOrCreate(['key' => 'start_time'], ['value' => $this->startTime]);
        Setting::updateOrCreate(['key' => 'end_time'], ['value' => $this->endTime]);
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Grid updated for the new semester!']);
    }

    /**
     * Generates 12-hour format time slots dynamically
     * Skips 12:00 PM - 1:00 PM for lunch
     */
    public function getTimeSlotsProperty()
    {
        $slots = [];
        $current = strtotime($this->startTime);
        $end = strtotime($this->endTime);

        while ($current < $end) {
            $timeString = date('H:i', $current);

            // Skip Lunch Break
            if ($timeString == '12:00') {
                $current = strtotime('+1 hour', $current);
                continue;
            }

            $next = strtotime('+' . $this->interval . ' minutes', $current);
            
            $slots[] = [
                'display' => date('h:i A', $current) . ' - ' . date('h:i A', $next),
                'start' => date('H:i:s', $current),
                'end' => date('H:i:s', $next),
            ];

            $current = $next;
        }
        return $slots;
    }

    public function selectRoom($id)
    {
        $room = Room::find($id);
        if ($room) {
            $this->selectedRoomId = $id;
            $this->selectedRoomName = $room->room_name;
        }
    }

    public function assignSubject($subjectId, $day, $startTime, $endTime)
    {
        // 1. Guard: Room and Subject must be selected
        if (!$this->selectedRoomId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a room first!']);
            return;
        }

        if (!$subjectId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a subject from the sidebar!']);
            return;
        }

        // 2. Conflict Check (Same Room, Same Time, Same Day)
        $exists = Schedule::where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->where('start_time', $startTime)
            ->exists();

        if ($exists) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'This slot is already occupied!']);
            return;
        }

        // 3. Create the Record
        Schedule::create([
            'subject_id' => $subjectId,
            'room_id'    => $this->selectedRoomId,
            'user_id'    => auth()->id() ?? 1, 
            'day'        => $day,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'section'    => $this->selectedSection ?: 'A',
        ]);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Successfully assigned to ' . $day]);
    }

    public function removeAssignment($id)
    {
        Schedule::destroy($id);
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Schedule removed.']);
    }

    public function render()    
    {
        $service = new ScheduleService();

        $subjects = Subject::query()
            ->when($this->searchSubject, fn($q) => 
                $q->where('subject_code', 'like', "%{$this->searchSubject}%")
                  ->orWhere('description', 'like', "%{$this->searchSubject}%")
            )
            ->when($this->selectedDept, fn($q) => $q->where('department', $this->selectedDept))
            ->orderBy('subject_code', 'asc')
            ->get();

        $rooms = Room::all()->map(function($room) use ($service) {
            $room->utilization = $service->getRoomLoad($room->id);
            return $room;
        });

        $schedules = Schedule::with(['subject'])
            ->when($this->selectedRoomId, fn($q) => $q->where('room_id', $this->selectedRoomId))
            ->get();

        return view('livewire.master-grid', [
            'subjects' => $subjects,
            'rooms' => $rooms,
            'schedules' => $schedules,
            'gridSlots' => $this->timeSlots
        ]);
    }
}