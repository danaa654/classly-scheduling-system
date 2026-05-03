<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\WithPagination;

class MasterGrid extends Component
{
    use WithPagination;

    public $searchSubject = '';
    public $selectedDept = null;
    public $selectedYear = '';
    public $selectedMajor = '';

    #[Url]
    public $selectedRoomId = null;
    public $selectedRoomName = null;
    public $selectedRoomType = null;
    public $selectedSection = 'A';

    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    public $startTime = '07:00';
    public $endTime = '21:00';
    
    // Lunch break times
    public $lunchBreakStart = '12:00';
    public $lunchBreakEnd = '13:00';
    
    // 30-minute brick constant
    public const BRICK_DURATION_MINUTES = 30;
    
    // UI Constants
    public const BRICK_HEIGHT_PX = 45;
    public const TIME_COL_WIDTH = 'w-24';
    public const DAY_COL_WIDTH = 'flex-1';
    
    // Time format constants
    public const TIME_FORMAT_12H = 'h:i A';
    public const TIME_FORMAT_24H = 'H:i:s';

    protected $listeners = ['refreshGrid' => '$refresh'];

    public function mount()
    {
        $this->startTime = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->endTime = Setting::where('key', 'end_time')->first()?->value ?? '21:00';
        $this->lunchBreakStart = Setting::where('key', 'lunch_break_start')->first()?->value ?? '12:00';
        $this->lunchBreakEnd = Setting::where('key', 'lunch_break_end')->first()?->value ?? '13:00';
    }

    public function updatedSearchSubject() { $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedMajor() { $this->resetPage(); }
    public function updatedSelectedDept() { $this->selectedMajor = ''; $this->resetPage(); }

    /**
     * Format time to 12-hour format with AM/PM
     */
    public function formatTime12h($time): string
    {
        return Carbon::parse($time)->format(self::TIME_FORMAT_12H);
    }

    /**
     * Format time range to 12-hour format
     */
    public function formatTimeRange12h($startTime, $endTime): string
    {
        $start = Carbon::parse($startTime)->format(self::TIME_FORMAT_12H);
        $end = Carbon::parse($endTime)->format(self::TIME_FORMAT_12H);
        return "{$start} - {$end}";
    }

    /**
     * Calculate minutes per meeting.
     */
    public function calculateMinutesPerMeeting($subject): float
    {
        $totalMinutes = ($subject->duration_hours ?? 0) * 60;
        $meetingsPerWeek = $subject->meetings_per_week ?? 1;

        if ($meetingsPerWeek <= 0) {
            return self::BRICK_DURATION_MINUTES;
        }

        return $totalMinutes / $meetingsPerWeek;
    }

    /**
     * Calculate remaining hours for a subject (as decimal).
     */
    public function getRemainingHoursDecimal($subjectId): float
    {
        $subject = Subject::find($subjectId);
        if (!$subject) return 0;

        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        $scheduledCount = $this->getScheduledCount($subjectId);
        $usedMinutes = $minutesPerMeeting * $scheduledCount;
        $totalMinutes = ($subject->duration_hours ?? 0) * 60;
        $remainingMinutes = max(0, $totalMinutes - $usedMinutes);
        
        return round($remainingMinutes / 60, 1);
    }

    /**
     * Calculate the number of bricks (30-min increments) needed.
     */
    public function calculateBrickCount($subject): int
    {
        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        return (int)ceil($minutesPerMeeting / self::BRICK_DURATION_MINUTES);
    }

    /**
     * Calculate total height in pixels for a subject card.
     */
    public function calculateCardHeightPx($subject): int
    {
        $brickCount = $this->calculateBrickCount($subject);
        return ($brickCount * self::BRICK_HEIGHT_PX) + ($brickCount - 1);
    }

    /**
     * Calculate end time by adding minutes to start time.
     */
    public function calculateEndTime($startTime, $minutesToAdd): string
    {
        return Carbon::parse($startTime)
            ->addMinutes($minutesToAdd)
            ->format(self::TIME_FORMAT_24H);
    }

    /**
     * Validate that a time is aligned to 30-minute increments.
     */
    private function isTimeAlignedTo30Min($time): bool
    {
        $minutes = (int)Carbon::parse($time)->format('i');
        return in_array($minutes, [0, 30], true);
    }

    /**
     * Get scheduled count for a subject.
     */
    public function getScheduledCount($subjectId): int
    {
        return Schedule::where('subject_id', $subjectId)->count();
    }

    /**
     * Calculate remaining hours for a subject (legacy compatibility).
     */
    public function getRemainingHours($subjectId): float
    {
        return $this->getRemainingHoursDecimal($subjectId);
    }

    /**
     * Get remaining meetings.
     */
    public function getRemainingMeetings($subject): int
    {
        $scheduledCount = $this->getScheduledCount($subject->id);
        return max(0, ($subject->meetings_per_week ?? 1) - $scheduledCount);
    }

    /**
     * Check if a time slot overlaps with lunch break.
     */
    private function overlapsLunchBreak($startTime, $endTime): bool
    {
        $lunchStart = Carbon::parse($this->lunchBreakStart);
        $lunchEnd = Carbon::parse($this->lunchBreakEnd);
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);

        return $slotStart < $lunchEnd && $slotEnd > $lunchStart;
    }

    /**
     * Check if a time slot is within lunch break hours.
     */
    public function isLunchBreakTime($startTime, $endTime): bool
    {
        return $this->overlapsLunchBreak($startTime, $endTime);
    }

    /**
     * Check if a time slot overlaps with existing schedules in the room.
     */
    private function hasRoomConflict($roomId, $day, $startTime, $endTime): bool
    {
        return Schedule::where('room_id', $roomId)
            ->where('day', $day)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    /**
     * Check if faculty member is double-booked.
     */
    private function hasFacultyConflict($subjectId, $day, $startTime, $endTime): bool
    {
        $subject = Subject::find($subjectId);
        if (!$subject || !$subject->faculty_id) {
            return false;
        }

        return Schedule::where('day', $day)
            ->whereHas('subject', function ($query) use ($subject) {
                $query->where('faculty_id', $subject->faculty_id);
            })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    /**
     * Generate hourly display slots (one row per 30-minute brick).
     */
    public function generateDisplaySlots()
    {
        $slots = [];
        $current = Carbon::parse($this->startTime);
        $end = Carbon::parse($this->endTime);
        $lunchStart = Carbon::parse($this->lunchBreakStart);
        $lunchEnd = Carbon::parse($this->lunchBreakEnd);

        while ($current < $end) {
            // Skip lunch break entirely
            if ($current >= $lunchStart && $current < $lunchEnd) {
                $current = $current->copy()->setTimeFromTimeString($this->lunchBreakEnd);
                continue;
            }

            $next = $current->copy()->addMinutes(self::BRICK_DURATION_MINUTES);
            if ($next > $end) break;

            $slots[] = [
                'display' => $this->formatTimeRange12h($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
                'start' => $current->format(self::TIME_FORMAT_24H),
                'end' => $next->format(self::TIME_FORMAT_24H),
            ];

            $current = $next;
        }

        return $slots;
    }

    /**
     * Get lunch break slots for special visual treatment.
     */
    public function getLunchBreakSlots()
    {
        $slots = [];
        $current = Carbon::parse($this->lunchBreakStart);
        $end = Carbon::parse($this->lunchBreakEnd);

        while ($current < $end) {
            $next = $current->copy()->addMinutes(self::BRICK_DURATION_MINUTES);
            if ($next > $end) break;

            $slots[] = [
                'display' => $this->formatTimeRange12h($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
                'start' => $current->format(self::TIME_FORMAT_24H),
                'end' => $next->format(self::TIME_FORMAT_24H),
            ];

            $current = $next;
        }

        return $slots;
    }

    /**
     * Calculate room utilization based on bricks occupied in the entire week.
     */
    public function calculateRoomUtilization($room): int
    {
        $totalBricksPerDay = count($this->generateDisplaySlots());
        $totalDaysPerWeek = 6; // MON-SAT
        $totalAvailableBricks = $totalBricksPerDay * $totalDaysPerWeek;

        if ($totalAvailableBricks === 0) {
            return 0;
        }

        $scheduledSubjects = Schedule::where('room_id', $room->id)
            ->with('subject')
            ->get();

        $occupiedBricks = 0;
        foreach ($scheduledSubjects as $schedule) {
            $subject = $schedule->subject;
            if (!$subject) continue;

            $brickCount = $this->calculateBrickCount($subject);
            $occupiedBricks += $brickCount;
        }

        $utilization = ($occupiedBricks / $totalAvailableBricks) * 100;
        return min(100, (int)round($utilization));
    }

    /**
     * Calculate the grid row span based on subject duration.
     */
    public function calculateGridRowSpan($subject): int
    {
        return $this->calculateBrickCount($subject);
    }

    /**
     * Find the grid row index for a given start time.
     */
    private function findGridRowIndex($time)
{
    $time = \Carbon\Carbon::parse($time);
    $gridStart = \Carbon\Carbon::parse('07:00:00');
    
    // Calculate exact minutes from grid start
    $diffMinutes = $gridStart->diffInMinutes($time);
    
    // Each slot is exactly 30 minutes = 45px
    $slotIndex = round($diffMinutes / 30);
    
    return (int)$slotIndex;
}

    /**
     * Get room by ID with full details for tooltip display.
     */
    public function getRoomDetails($roomId): ?array
    {
        $room = Room::find($roomId);
        if (!$room) return null;

        return [
            'id' => $room->id,
            'name' => $room->room_name,
            'type' => $room->type,
            'capacity' => $room->capacity,
            'utilization' => $this->calculateRoomUtilization($room),
        ];
    }

    public function selectRoom($id)
    {
        $room = Room::find($id);
        if ($room) {
            $this->selectedRoomId = $id;
            $this->selectedRoomName = $room->room_name;
            $this->selectedRoomType = $room->type;
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '✅ Room Selected',
                'detail' => "Room {$room->room_name} is now active for scheduling"
            ]);
        }
    }

    /**
     * Validate room selection before assignment.
     */
    public function validateRoomSelection(): bool
    {
        if (!$this->selectedRoomId) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⚠️ Select a Room First',
                'detail' => 'Please select a room from the sidebar before assigning subjects to the grid.'
            ]);
            return false;
        }
        return true;
    }

    /**
     * Assign subject to a time slot with comprehensive validation.
     */
    public function assignSubject($subjectId, $day, $startTime)
    {
        // Validation 1: Room selected
        if (!$this->selectedRoomId) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⚠️ Select a Room First',
                'detail' => 'Please select a room from the sidebar before assigning subjects.'
            ]);
            return;
        }

        // Validation 2: Valid subject ID
        if (!$subjectId) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Invalid Subject',
                'detail' => 'Please select a valid subject!'
            ]);
            return;
        }

        // Validation 3: Subject exists
        $subject = Subject::find($subjectId);
        if (!$subject) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Subject Not Found',
                'detail' => 'The selected subject could not be found!'
            ]);
            return;
        }

        // Validation 4: Remaining meetings
        $remaining = $this->getRemainingMeetings($subject);
        if ($remaining <= 0) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⏸️ No Meetings Left',
                'detail' => "All {$subject->meetings_per_week} meetings for {$subject->subject_code} have been scheduled!"
            ]);
            return;
        }

        // Validation 5: Time is aligned to 30-minute increments
        if (!$this->isTimeAlignedTo30Min($startTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🧱 Invalid Time Slot',
                'detail' => 'Start time must align to 30-minute increments (:00 or :30).'
            ]);
            return;
        }

        // Calculate minutes and end time
        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        $endTime = $this->calculateEndTime($startTime, $minutesPerMeeting);
        $brickCount = $this->calculateBrickCount($subject);

        // Validation 6: Check lunch break collision
        if ($this->overlapsLunchBreak($startTime, $endTime)) {
            $lunchDisplay = $this->formatTimeRange12h($this->lunchBreakStart, $this->lunchBreakEnd);
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🍽️ Lunch Break Blocked',
                'detail' => "Cannot schedule during lunch break ({$lunchDisplay}). Please select another time."
            ]);
            return;
        }

        // Validation 7: Check room time conflict
        if ($this->hasRoomConflict($this->selectedRoomId, $day, $startTime, $endTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🔴 Room Time Conflict',
                'detail' => 'This room is already occupied during this time. Choose another slot!'
            ]);
            return;
        }

        // Validation 8: Check faculty conflict
        if ($this->hasFacultyConflict($subjectId, $day, $startTime, $endTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '👨‍🏫 Faculty Conflict',
                'detail' => 'This faculty member is teaching another class at this time!'
            ]);
            return;
        }

        // All validations passed - create the schedule
        try {
            Schedule::create([
                'subject_id' => $subjectId,
                'room_id' => $this->selectedRoomId,
                'user_id' => auth()->id() ?? 1,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'section' => $this->selectedSection ?: 'A',
            ]);

            $remainingHours = $this->getRemainingHoursDecimal($subjectId);
            $remainingMeetings = $this->getRemainingMeetings($subject) - 1;

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '✅ Subject Scheduled',
                'detail' => "{$subject->subject_code} scheduled on {$day}. {$remainingMeetings} meetings remaining ({$remainingHours}h left)."
            ]);

            $this->dispatch('refreshGrid');
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Error',
                'detail' => 'Failed to schedule subject: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove a schedule.
     */
    public function removeAssignment($scheduleId)
    {
        try {
            $schedule = Schedule::find($scheduleId);
            if ($schedule) {
                $subject = $schedule->subject;
                $schedule->delete();

                $this->dispatch('toast', [
                    'type' => 'info',
                    'message' => '🗑️ Schedule Removed',
                    'detail' => "{$subject->subject_code} has been removed from {$schedule->day}"
                ]);

                $this->dispatch('refreshGrid');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Error',
                'detail' => 'Failed to remove schedule: ' . $e->getMessage()
            ]);
        }
    }

    public function getFilteredSubjects()
    {
        $subjects = Subject::query()
            ->select('id', 'subject_code', 'description', 'edp_code', 'duration_hours', 'meetings_per_week', 'units', 'department', 'section')
            ->when(trim($this->searchSubject), function ($query) {
                $query->where(function ($q) {
                    $q->where('subject_code', 'like', '%' . $this->searchSubject . '%')
                      ->orWhere('description', 'like', '%' . $this->searchSubject . '%')
                      ->orWhere('edp_code', 'like', '%' . $this->searchSubject . '%');
                });
            })
            ->when($this->selectedDept, function ($query) {
                $query->where('department', $this->selectedDept);
            })
            ->when($this->selectedYear, function ($query) {
                $query->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(edp_code, '-', 3), '-', -1) LIKE ?", ["{$this->selectedYear}%"]);
            })
            ->when($this->selectedMajor, function ($query) {
                $major = strtoupper($this->selectedMajor);
                $query->where(function ($q) use ($major) {
                    $q->where('subject_code', 'like', $major . '%')
                      ->orWhere('edp_code', 'like', "%-{$major}-%");
                });
            })
            ->where('meetings_per_week', '>', 0)
            ->orderBy('subject_code', 'asc')
            ->get()
            ->filter(function($subject) {
                return $this->getRemainingHours($subject->id) > 0;
            });

        return $subjects;
    }

    /**
     * Calculate the exact pixel height for a schedule card based on duration.
     */
    public function calculateScheduleHeightPx($startTime, $endTime): int
    {
        $durationMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        $brickCount = ceil($durationMinutes / self::BRICK_DURATION_MINUTES);
        return ($brickCount * self::BRICK_HEIGHT_PX) + max(0, ($brickCount - 1));
    }

    /**
     * Get the top offset (row index × 45px) for a schedule's start time.
     */
    public function getScheduleTopOffset($startTime): int
    {
        $slots = $this->generateDisplaySlots();
        $index = 0;
        
        foreach ($slots as $i => $slot) {
            if ($slot['start'] === $startTime) {
                $index = $i;
                break;
            }
        }
        
        return $index * self::BRICK_HEIGHT_PX;
    }

    /**
     * Get display time for schedule card
     */
    public function getScheduleDisplayTime($startTime, $endTime): string
    {
        $start = Carbon::parse($startTime)->format('h:i A');
        $end = Carbon::parse($endTime)->format('h:i A');
        return "{$start} - {$end}";
    }

    /**
     * Check if a schedule is the first occurrence in its slot
     */
    public function isScheduleStartingAtSlot($schedule, $slotStartTime): bool
    {
        return $schedule->start_time === $slotStartTime;
    }

    /**
     * Check if a slot is within a schedule's time range
     */
    public function isSlotWithinSchedule($schedule, $slotStart, $slotEnd): bool
    {
        $schedStart = Carbon::parse($schedule->start_time);
        $schedEnd = Carbon::parse($schedule->end_time);
        $slotStartCarbon = Carbon::parse($slotStart);
        $slotEndCarbon = Carbon::parse($slotEnd);
        
        return $schedStart <= $slotStartCarbon && $schedEnd > $slotStartCarbon;
    }

    /**
     * NEW: Get overlapping schedules for a given day and time slot
     * Returns array with position data for side-by-side rendering
     */
    public function getOverlappingSchedulesForSlot($day, $slotStart, $slotEnd): array
    {
        if (!$this->selectedRoomId) {
            return [];
        }

        $schedules = Schedule::where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->with('subject')
            ->get()
            ->filter(function ($schedule) use ($slotStart, $slotEnd) {
                $schedStart = Carbon::parse($schedule->start_time);
                $schedEnd = Carbon::parse($schedule->end_time);
                $slotStartCarbon = Carbon::parse($slotStart);
                $slotEndCarbon = Carbon::parse($slotEnd);
                
                return $schedStart < $slotEndCarbon && $schedEnd > $slotStartCarbon;
            })
            ->values();

        // Calculate positions for each overlapping schedule
        $result = [];
        $totalOverlaps = $schedules->count();

        foreach ($schedules as $index => $schedule) {
            $result[] = [
                'schedule' => $schedule,
                'position' => $index,
                'totalOverlaps' => $totalOverlaps,
                'leftPercent' => ($index / $totalOverlaps) * 100,
                'widthPercent' => (100 / $totalOverlaps),
            ];
        }

        return $result;
    }

    /**
     * Get all schedules for a specific day and time that overlap
     */
    public function getSchedulesAtSlot($day, $slotStart, $slotEnd): array
    {
        if (!$this->selectedRoomId) {
            return [];
        }

        return Schedule::where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->with('subject')
            ->whereRaw('start_time < ? AND end_time > ?', [$slotEnd, $slotStart])
            ->get()
            ->toArray();
    }

    

    public function render()
    {
        $subjects = $this->getFilteredSubjects();

        $rooms = Room::all()->map(function ($room) {
            $room->utilization = $this->calculateRoomUtilization($room);
            return $room;
        })->sortBy('room_name');

        $schedules = $this->selectedRoomId
            ? Schedule::where('room_id', $this->selectedRoomId)
                ->with(['subject' => function ($query) {
                    $query->select('id', 'subject_code', 'description', 'edp_code', 'duration_hours', 'department');
                }])
                ->get()
            : collect();

        $lunchSlots = $this->getLunchBreakSlots();

        return view('livewire.master-grid', [
            'days' => ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
            'displaySlots' => $this->generateDisplaySlots(),
            'lunchSlots' => $lunchSlots,
            'schedules' => $schedules,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'lunchBreakStart' => $this->lunchBreakStart,
            'lunchBreakEnd' => $this->lunchBreakEnd,
            'brickDurationMinutes' => self::BRICK_DURATION_MINUTES,
            'brickHeightPx' => self::BRICK_HEIGHT_PX,
        ]);
    }
}