<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Schedule;

class MasterGrid extends Component
{
    // Selection Filters
    public $selectedRoomId;
    public $selectedRoomName;
    public $selectedDept;
    public $selectedYear;
    public $selectedSection;

    // Constants for the Grid
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    public $timeSlots = [
        '07:30 AM - 09:00 AM',
        '09:00 AM - 10:30 AM',
        '10:30 AM - 12:00 PM',
        '01:00 PM - 02:30 PM',
        '02:30 PM - 04:00 PM',
        '04:00 PM - 05:30 PM',
    ];

    /**
     * Updates the grid to focus on a specific room's schedule.
     */
    public function selectRoom($id) 
    {
        $this->selectedRoomId = $id;
        $room = Room::find($id);
        // Matching your DB column 'room_name'
        $this->selectedRoomName = $room ? $room->room_name : null;
        
        // Reset class filters when a room is specifically selected
        $this->reset(['selectedDept', 'selectedYear', 'selectedSection']);
    }

    /**
     * Handles the Drag-and-Drop assignment of a subject to a slot.
     */
    public function assignSubject($subjectId, $day, $timeSlot) 
    {
        if (!$this->selectedRoomId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a room from the right panel first!']);
            return;
        }

        // 1. Validate if the room slot is already taken
        $exists = Schedule::where('room_id', $this->selectedRoomId)
                          ->where('day', $day)
                          ->where('time_slot', $timeSlot)
                          ->exists();

        if ($exists) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'This room is already occupied at this time!']);
            return;
        }

        // 2. Optional: Add a check if the SUBJECT is already scheduled elsewhere at this time
        // (Prevents a teacher/class being in two places at once)

        Schedule::create([
            'subject_id' => $subjectId,
            'room_id' => $this->selectedRoomId,
            'day' => $day,
            'time_slot' => $timeSlot,
        ]);
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Schedule updated successfully!']);
    }

    public function removeAssignment($id)
    {
        Schedule::find($id)?->delete();
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Schedule removed.']);
    }

    public function render()
    {
        // Calculate constants outside the loop to avoid 'lexical variable' errors
        $daysCount = count($this->days);
        $slotsCount = count($this->timeSlots);
        $maxPossibleSlots = $daysCount * $slotsCount;

        // 1. Fetch Rooms with calculated utilization
        // We use 'use' to bring the external variables into the function scope
        $rooms = Room::all()->map(function($room) use ($maxPossibleSlots) {
            $actualBookedSlots = Schedule::where('room_id', $room->id)->count();

            $room->utilization = $maxPossibleSlots > 0 
                ? round(($actualBookedSlots / $maxPossibleSlots) * 100) 
                : 0;

            return $room;
        });

        // 2. Fetch Subjects for the sidebar
        $subjects = Subject::all();

        // 3. Determine which schedules to show in the grid
        $scheduleQuery = Schedule::with(['subject', 'room']);

        if ($this->selectedRoomId) {
            $scheduleQuery->where('room_id', $this->selectedRoomId);
        } elseif ($this->selectedDept || $this->selectedYear || $this->selectedSection) {
            // Using Arrow Function (fn) to avoid 'use' syntax for cleaner code
            $scheduleQuery->whereHas('subject', fn($q) => 
                $q->when($this->selectedDept, fn($sub) => $sub->where('department', $this->selectedDept))
                  ->when($this->selectedYear, fn($sub) => $sub->where('year_level', $this->selectedYear))
                  ->when($this->selectedSection, fn($sub) => $sub->where('section', $this->selectedSection))
            );
        } else {
            // Default view: Show nothing or show a specific default
            $scheduleQuery->where('id', 0); 
        }

        return view('livewire.master-grid', [
            'rooms' => $rooms,
            'subjects' => $subjects,
            'schedules' => $scheduleQuery->get(),
        ])->layout('layouts.app');
    }
}