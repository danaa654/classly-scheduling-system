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
    public $selectedSection = null;
    public $selectedType = '';

    #[Url]
    public $selectedRoomId = null;
    public $selectedRoomName = null;
    public $selectedRoomType = null;

    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    public $startTime = '07:00';
    public $endTime = '21:00';
    
    public $lunchBreakStart = '12:00';
    public $lunchBreakEnd = '13:00';
    
    public const BRICK_DURATION_MINUTES = 30;
    
    public const BRICK_HEIGHT_PX = 45;
    public const TIME_COL_WIDTH = 'w-24';
    public const DAY_COL_WIDTH = 'flex-1';
    
    public const TIME_FORMAT_12H = 'h:i A';
    public const TIME_FORMAT_24H = 'H:i:s';

    public const DEPARTMENT_MAJORS = [
        'CCS' => ['IT', 'ACT'],     
        'COC' => ['FB', 'LD', 'QD'],
        'SHTM' => ['HM', 'TM'],
    ];

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
    public function updatedSelectedSection() { $this->resetPage(); }
    public function updatedSelectedType() { $this->resetPage(); }

    public function hasFullAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        $role = $user->role ?? 'guest';
        return in_array($role, ['admin', 'registrar', 'associate_dean']);
    }

    public function getUserDepartment(): ?string
    {
        $user = auth()->user();
        return $user->department ?? null;
    }

    public function canDeleteSchedule($scheduleId): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $schedule = Schedule::find($scheduleId);
        if (!$schedule || !$schedule->subject) {
            return false;
        }

        $user = auth()->user();
        $userRole = $user->role ?? 'guest';
        $userDept = $user->department ?? null;

        if (in_array($userRole, ['dean', 'oic'])) {
            return $schedule->subject->department === $userDept;
        }

        return false;
    }

    public function formatTime12h($time): string
    {
        return Carbon::parse($time)->format(self::TIME_FORMAT_12H);
    }

    public function formatTimeRange12h($startTime, $endTime): string
    {
        $start = Carbon::parse($startTime)->format(self::TIME_FORMAT_12H);
        $end = Carbon::parse($endTime)->format(self::TIME_FORMAT_12H);
        return "{$start} - {$end}";
    }

    public function calculateMinutesPerMeeting($subject): float
    {
        $totalMinutes = ($subject->duration_hours ?? 0) * 60;
        $meetingsPerWeek = $subject->meetings_per_week ?? 1;

        if ($meetingsPerWeek <= 0) {
            return self::BRICK_DURATION_MINUTES;
        }

        return $totalMinutes / $meetingsPerWeek;
    }

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

    public function calculateBrickCount($subject): int
    {
        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        return (int)ceil($minutesPerMeeting / self::BRICK_DURATION_MINUTES);
    }

    public function calculateCardHeightPx($subject): int
    {
        $brickCount = $this->calculateBrickCount($subject);
        return ($brickCount * self::BRICK_HEIGHT_PX) + ($brickCount - 1);
    }

    public function calculateEndTime($startTime, $minutesToAdd): string
    {
        return Carbon::parse($startTime)
            ->addMinutes($minutesToAdd)
            ->format(self::TIME_FORMAT_24H);
    }

    private function isTimeAlignedTo30Min($time): bool
    {
        $minutes = (int)Carbon::parse($time)->format('i');
        return in_array($minutes, [0, 30], true);
    }

    public function getScheduledCount($subjectId): int
    {
        return Schedule::where('subject_id', $subjectId)->count();
    }

    public function getRemainingHours($subjectId): float
    {
        return $this->getRemainingHoursDecimal($subjectId);
    }

    public function getRemainingMeetings($subject): int
    {
        $scheduledCount = $this->getScheduledCount($subject->id);
        return max(0, ($subject->meetings_per_week ?? 1) - $scheduledCount);
    }

    private function overlapsLunchBreak($startTime, $endTime): bool
    {
        $lunchStart = Carbon::parse($this->lunchBreakStart);
        $lunchEnd = Carbon::parse($this->lunchBreakEnd);
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);

        return $slotStart < $lunchEnd && $slotEnd > $lunchStart;
    }

    public function isLunchBreakTime($startTime, $endTime): bool
    {
        return $this->overlapsLunchBreak($startTime, $endTime);
    }

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

    public function generateDisplaySlots()
    {
        $slots = [];
        $current = Carbon::parse($this->startTime);
        $end = Carbon::parse($this->endTime);

        while ($current < $end) {
            $next = $current->copy()->addMinutes(self::BRICK_DURATION_MINUTES);
            if ($next > $end) break;

            $slots[] = [
                'display' => $this->formatTimeRange12h($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
                'start' => $current->format(self::TIME_FORMAT_24H),
                'end' => $next->format(self::TIME_FORMAT_24H),
                'isLunch' => $this->overlapsLunchBreak($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
            ];

            $current = $next;
        }

        return $slots;
    }

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

    public function calculateRoomUtilization($room): int
    {
        $totalBricksPerDay = count($this->generateDisplaySlots());
        $totalDaysPerWeek = 6;
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

    public function calculateGridRowSpan($subject): int
    {
        return $this->calculateBrickCount($subject);
    }

    private function findGridRowIndex($time)
    {
        $time = \Carbon\Carbon::parse($time);
        $gridStart = \Carbon\Carbon::parse('07:00:00');
        
        $diffMinutes = $gridStart->diffInMinutes($time);
        $slotIndex = round($diffMinutes / 30);
        
        return (int)$slotIndex;
    }

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
            
            $this->dispatch('room-selected');

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '✅ Room Selected',
                'detail' => "Room {$room->room_name} is now active"
            ]);
        }
    }

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

    public function assignSubject($subjectId, $day, $startTime)
    {
        if (!$this->selectedRoomId) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⚠️ Select a Room First',
                'detail' => 'Please select a room from the sidebar before assigning subjects.'
            ]);
            return;
        }

        if (!$subjectId) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Invalid Subject',
                'detail' => 'Please select a valid subject!'
            ]);
            return;
        }

        $subject = Subject::find($subjectId);
        if (!$subject) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Subject Not Found',
                'detail' => 'The selected subject could not be found!'
            ]);
            return;
        }

        $remaining = $this->getRemainingMeetings($subject);
        if ($remaining <= 0) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⏸️ No Meetings Left',
                'detail' => "All {$subject->meetings_per_week} meetings for {$subject->subject_code} have been scheduled!"
            ]);
            return;
        }

        if (!$this->isTimeAlignedTo30Min($startTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🧱 Invalid Time Slot',
                'detail' => 'Start time must align to 30-minute increments (:00 or :30).'
            ]);
            return;
        }

        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        $endTime = $this->calculateEndTime($startTime, $minutesPerMeeting);

        $gridEnd = Carbon::parse($this->endTime);
        $calculatedEndTime = Carbon::parse($endTime);
        
        if ($calculatedEndTime > $gridEnd) {
            $gridEndDisplay = $this->formatTime12h($this->endTime);
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '⏰ End Time Out of Bounds',
                'detail' => "This subject would end at {$this->formatTime12h($endTime)}, which exceeds the grid end time ({$gridEndDisplay}). Please select an earlier time slot."
            ]);
            return;
        }

        if ($this->overlapsLunchBreak($startTime, $endTime)) {
            $lunchDisplay = $this->formatTimeRange12h($this->lunchBreakStart, $this->lunchBreakEnd);
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🍽️ Lunch Break Blocked',
                'detail' => "Cannot schedule during lunch break ({$lunchDisplay}). Please select another time."
            ]);
            return;
        }

        if ($this->hasRoomConflict($this->selectedRoomId, $day, $startTime, $endTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🔴 Room Time Conflict',
                'detail' => 'This room is already occupied during this time. Choose another slot!'
            ]);
            return;
        }

        if ($this->hasFacultyConflict($subjectId, $day, $startTime, $endTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '👨‍🏫 Faculty Conflict',
                'detail' => 'This faculty member is teaching another class at this time!'
            ]);
            return;
        }

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

    public function removeAssignment($scheduleId)
    {
        if (!$this->canDeleteSchedule($scheduleId)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Permission Denied',
                'detail' => 'You don\'t have permission to delete this schedule. Only your department\'s schedules can be deleted.'
            ]);
            return;
        }

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
        $query = Subject::query()
            ->select('id', 'subject_code', 'description', 'edp_code', 'duration_hours', 'meetings_per_week', 'units', 'department', 'section', 'type'); // ✅ Add type here

        if (!$this->hasFullAccess()) {
            $userDept = $this->getUserDepartment();
            if ($userDept) {
                $query->where('department', $userDept);
            }
        }

        if (trim($this->searchSubject)) {
            $query->where(function ($q) {
                $q->where('subject_code', 'like', '%' . $this->searchSubject . '%')
                  ->orWhere('description', 'like', '%' . $this->searchSubject . '%')
                  ->orWhere('edp_code', 'like', '%' . $this->searchSubject . '%');
            });
        }

        if ($this->selectedDept) {
            $query->where('department', $this->selectedDept);
        }

        if ($this->selectedYear) {
            $query->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(edp_code, '-', 3), '-', -1) LIKE ?", ["{$this->selectedYear}%"]);
        }

        if ($this->selectedMajor) {
            $major = strtoupper($this->selectedMajor);
            $query->where(function ($q) use ($major) {
                $q->where('subject_code', 'like', $major . '%')
                  ->orWhere('edp_code', 'like', "%-{$major}-%");
            });
        }

        if ($this->selectedSection) {
            $query->where('section', $this->selectedSection);
        }

        // ✅ Type filtering
        if ($this->selectedType) {
            $type = ucfirst(strtolower($this->selectedType));
            $query->where('type', $type);
        }

        $subjects = $query
            ->where('meetings_per_week', '>', 0)
            ->orderBy('subject_code', 'asc')
            ->get()
            ->filter(function($subject) {
                return $this->getRemainingHours($subject->id) > 0;
            });

        return $subjects;
    }

    public function calculateScheduleHeightPx($startTime, $endTime): int
    {
        $durationMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        $brickCount = ceil($durationMinutes / self::BRICK_DURATION_MINUTES);
        return ($brickCount * self::BRICK_HEIGHT_PX) + max(0, ($brickCount - 1));
    }

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

    public function getScheduleDisplayTime($startTime, $endTime): string
    {
        $start = Carbon::parse($startTime)->format('h:i A');
        $end = Carbon::parse($endTime)->format('h:i A');
        return "{$start} - {$end}";
    }

    public function isScheduleStartingAtSlot($schedule, $slotStartTime): bool
    {
        return $schedule->start_time === $slotStartTime;
    }

    public function isSlotWithinSchedule($schedule, $slotStart, $slotEnd): bool
    {
        $schedStart = Carbon::parse($schedule->start_time);
        $schedEnd = Carbon::parse($schedule->end_time);
        $slotStartCarbon = Carbon::parse($slotStart);
        $slotEndCarbon = Carbon::parse($slotEnd);
        
        return $schedStart <= $slotStartCarbon && $schedEnd > $slotStartCarbon;
    }

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
                    $query->select('id', 'subject_code', 'description', 'edp_code', 'duration_hours', 'department', 'type'); // ✅ Add type
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
            'hasFullAccess' => $this->hasFullAccess(),
            'departmentMajors' => self::DEPARTMENT_MAJORS,
        ]);
    }
}