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
    public $search = '';
    public $selectedDept = null;
    public $selectedYear = '';
    public $selectedMajor = '';
    public $roomType = '';
    
    // UI State properties
    #[Url]
    public $selectedRoomId = null;
    public $selectedRoomName = null;
    public $selectedSection = 'A'; 
    public $activeSubjectId = null;

    // Grid Config - NOW DYNAMIC
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    public $startTime = '07:00'; 
    public $endTime = '18:00';   
    public $slotDuration = 60; // Changed from 'interval'

    /**
     * Load dynamic settings from database
     */
    public function mount()
    {
        $this->loadSettings();
    }

    /**
     * Load all settings from the Settings table
     */
    private function loadSettings()
    {
        $this->startTime = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->endTime = Setting::where('key', 'end_time')->first()?->value ?? '18:00';
        
        // Convert default_duration (hours like 1.0, 1.5) to minutes
        $durationHours = floatval(Setting::where('key', 'default_duration')->first()?->value ?? '1.0');
        $this->slotDuration = (int)($durationHours * 60); // Convert to minutes
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
     * Generates time slots dynamically based on settings
     * Uses default_duration from settings for slot intervals
     * Skips 12:00 PM - 1:00 PM for lunch
     */
    public function generateTimeSlots()
    {
        $slots = [];
        $current = Carbon::parse($this->startTime);
        $end = Carbon::parse($this->endTime);

        while ($current < $end) {
            $timeString = $current->format('H:i');

            // Skip Lunch Break (12:00 PM - 1:00 PM)
            if ($timeString === '12:00') {
                $current->addMinutes($this->slotDuration);
                continue;
            }

            $next = $current->copy()->addMinutes($this->slotDuration);
            
            // Don't create a slot that goes past end time
            if ($next > $end) {
                break;
            }

            $slots[] = [
                'display' => $current->format('h:i A') . ' - ' . $next->format('h:i A'),
                'start' => $current->format('H:i:s'),
                'end' => $next->format('H:i:s'),
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

    public function getFilteredSubjects()
    {
        return Subject::query()
            ->when(trim($this->searchSubject), function($query) {
                $query->where(function($q) {
                    $q->where('description', 'like', '%' . $this->searchSubject . '%')
                      ->orWhere('subject_code', 'like', '%' . $this->searchSubject . '%')
                      ->orWhere('edp_code', 'like', '%' . $this->searchSubject . '%');
                });
            })
            ->when($this->selectedDept, function($query) {
                $query->where('edp_code', 'like', $this->selectedDept . '%');
            })
            ->orderBy('subject_code', 'asc')
            ->get();
    }

    public function render()    
    {
        // Reload settings in case they were changed elsewhere
        $this->loadSettings();

        $service = new ScheduleService();

        $subjects = $this->getFilteredSubjects();

        $rooms = Room::all()->map(function($room) use ($service) {
            $room->utilization = $service->getRoomLoad($room->id);
            return $room;
        });

        return view('livewire.master-grid', [
            'days' => ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
            'gridSlots' => $this->generateTimeSlots(), // NOW DYNAMIC
            'schedules' => Schedule::when($this->selectedRoomId, fn($q) => $q->where('room_id', $this->selectedRoomId))
                            ->with(['subject'])
                            ->get(),
            'subjects' => $subjects,
            'rooms' => Room::when($this->roomType, fn($q) => $q->where('type', $this->roomType))->get(),
        ]);
    }
}